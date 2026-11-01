<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\DonationReceived;
use HasinHayder\Tyro\Support\TyroAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DonationService
{
    public function __construct(
        private readonly FeeCalculationService $feeCalculationService
    ) {}

    /**
     * Process a donation.
     *
     * All financial values are computed server-side. The client only supplies
     * the gross amount, currency, anonymity preference, message, and — for
     * manual transfers — the sender mobile and provider transaction ID.
     * Platform fee rates are snapshotted at the time of donation and stored
     * on the record — changing fee configs later never mutates history.
     *
     * Every payment method on this platform is a manual transfer today
     * (bKash, Nagad, bank transfer). No gateway integration (bKash Merchant,
     * Stripe) is wired up, so a donation is NEVER marked completed here —
     * that would simulate a payment that was never verified. The record is
     * created as pending and an admin confirms it after checking the actual
     * transfer, at which point the ledger entry is written and raised_amount
     * is updated. See complete() below.
     *
     * The entire operation runs inside a single DB transaction with pessimistic
     * locking on the campaign to prevent race conditions on raised_amount.
     *
     * @param  array{
     *   gross_amount: float,
     *   currency: string,
     *   is_anonymous: bool,
     *   donor_name: ?string,
     *   donor_email: ?string,
     *   message: ?string,
     *   payment_method: ?string,
     *   sender_mobile: ?string,
     *   transaction_id: ?string,
     * }  $data
     *
     * @throws ValidationException
     */
    public function donate(Campaign $campaign, array $data, ?User $donor = null): Donation
    {
        $this->assertCampaignCanAcceptDonations($campaign);

        $grossAmount = (float) $data['gross_amount'];
        $minimum = $this->feeCalculationService->minimumDonation();

        if ($grossAmount < $minimum) {
            throw ValidationException::withMessages([
                'gross_amount' => "The minimum donation amount is {$campaign->currency} {$minimum}.",
            ]);
        }

        $fees = $this->feeCalculationService->calculate($grossAmount);

        return DB::transaction(function () use ($campaign, $data, $donor, $fees) {
            // Lock the campaign row to prevent concurrent raised_amount drift
            $campaign = Campaign::lockForUpdate()->findOrFail($campaign->id);

            $this->assertCampaignCanAcceptDonations($campaign);

            // Manual-verification flow: pending until an admin confirms the
            // actual transfer. Never auto-complete a payment.
            $status = 'pending';

            $donation = Donation::create([
                'campaign_id' => $campaign->id,
                'user_id' => $donor?->id,
                'donor_name' => $data['donor_name'] ?? $donor?->name,
                'donor_email' => $data['donor_email'] ?? $donor?->email,
                'is_anonymous' => $data['is_anonymous'] ?? false,
                'gross_amount' => $fees['gross_amount'],
                'platform_fee_pct' => $fees['platform_fee_pct'],
                'platform_fee_amount' => $fees['platform_fee_amount'],
                'payment_processing_fee' => $fees['payment_processing_fee'],
                'net_amount' => $fees['net_amount'],
                'currency' => $data['currency'] ?? $campaign->currency,
                'payment_method' => $data['payment_method'] ?? null,
                // Provider reference for manual transfers (bKash/Nagad TrxID).
                // Unique in the table — the same transfer can't be donated twice.
                'gateway_transaction_id' => $data['transaction_id'] ?? null,
                'sender_mobile' => $data['sender_mobile'] ?? null,
                'status' => $status,
                'message' => $data['message'] ?? null,
                'donated_at' => null, // set when an admin verifies the transfer
            ]);

            // No ledger entry, no raised_amount change and no organizer
            // notification yet — the payment is not confirmed. All of that
            // happens in complete() once an admin verifies the transfer.

            TyroAudit::log('donation.submitted', $donation, null, [
                'campaign_id' => $campaign->id,
                'gross_amount' => $fees['gross_amount'],
                'currency' => $donation->currency,
                'payment_method' => $donation->payment_method,
            ]);

            return $donation;
        });
    }

    /**
     * Admin action: the manual transfer was checked and the money arrived.
     *
     * Moves a pending donation to completed inside a single transaction:
     * writes the immutable ledger entry, bumps the campaign's raised_amount
     * with pessimistic locking, and notifies the organizer.
     *
     * @throws ValidationException
     */
    public function complete(Donation $donation, User $admin): Donation
    {
        if ($donation->status !== 'pending') {
            throw ValidationException::withMessages([
                'donation' => 'Only pending donations can be verified.',
            ]);
        }

        return DB::transaction(function () use ($donation, $admin) {
            $campaign = Campaign::lockForUpdate()->findOrFail($donation->campaign_id);

            $donation->status = 'completed';
            $donation->donated_at = now();
            $donation->save();

            // Immutable ledger entry — fee snapshot comes from the donation
            // record itself, so verification can never alter financial history.
            Transaction::create([
                'transactionable_type' => Donation::class,
                'transactionable_id' => $donation->id,
                'type' => 'donation',
                'amount' => $donation->gross_amount,
                'currency' => $donation->currency,
                'description' => "Donation to campaign #{$campaign->id}",
                'meta' => [
                    'campaign_id' => $campaign->id,
                    'platform_fee_pct' => $donation->platform_fee_pct,
                    'platform_fee_amount' => $donation->platform_fee_amount,
                    'payment_processing_fee' => $donation->payment_processing_fee,
                    'net_amount' => $donation->net_amount,
                ],
                'created_at' => now(),
            ]);

            $campaign->increment('raised_amount', $donation->gross_amount);

            // Notify the organizer of the verified donation (best-effort).
            $campaign->organizerProfile?->user?->notify(new DonationReceived($donation));

            TyroAudit::log('donation.completed', $donation, ['status' => 'pending'], [
                'status' => 'completed',
                'admin_id' => $admin->id,
                'campaign_id' => $campaign->id,
            ]);

            return $donation;
        });
    }

    /**
     * Admin action: the transfer could not be confirmed (or was reversed).
     *
     * Marks the pending donation as cancelled. It never touches raised_amount
     * and never produced a ledger entry, so financial records stay consistent.
     *
     * @throws ValidationException
     */
    public function reject(Donation $donation, User $admin): Donation
    {
        if ($donation->status !== 'pending') {
            throw ValidationException::withMessages([
                'donation' => 'Only pending donations can be rejected.',
            ]);
        }

        $donation->status = 'cancelled';
        $donation->save();

        TyroAudit::log('donation.cancelled', $donation, ['status' => 'pending'], [
            'status' => 'cancelled',
            'admin_id' => $admin->id,
        ]);

        return $donation;
    }

    /**
     * Central guard for "can this campaign take money right now?".
     * Status, deadline and goal are all checked server-side — the frontend
     * can hide the button, but this is what actually protects the money.
     *
     * @throws ValidationException
     */
    private function assertCampaignCanAcceptDonations(Campaign $campaign): void
    {
        if ($campaign->status !== 'active') {
            throw ValidationException::withMessages([
                'campaign' => 'This campaign is not currently accepting donations.',
            ]);
        }

        if ($campaign->end_date !== null && $campaign->end_date->isPast()) {
            throw ValidationException::withMessages([
                'campaign' => 'This campaign has ended and is no longer accepting donations.',
            ]);
        }

        if ($campaign->goal_amount > 0 && (float) $campaign->raised_amount >= (float) $campaign->goal_amount) {
            throw ValidationException::withMessages([
                'campaign' => 'This campaign has reached its goal and is no longer accepting donations.',
            ]);
        }
    }
}
