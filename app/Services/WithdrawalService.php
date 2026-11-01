<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\OrganizerProfile;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalCompleted;
use App\Notifications\WithdrawalFailed;
use HasinHayder\Tyro\Support\TyroAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Handles withdrawal requests and their immutable ledger entries.
 *
 * All fee values are computed server-side from the current fee configuration
 * and snapshotted onto the withdrawal record, so changing rates later never
 * mutates history. The actual money movement (Transaction ledger entry) is
 * recorded only when a withdrawal is completed by an admin.
 */
class WithdrawalService
{
    public function __construct(
        private readonly FeeCalculationService $feeCalculationService
    ) {}

    /**
     * Compute the organizer's available (withdrawable) balance.
     *
     * Sum of net amounts from completed donations across the organizer's
     * campaigns, minus the sum of already-completed withdrawals.
     */
    public function availableBalance(OrganizerProfile $profile): float
    {
        $campaignIds = $profile->campaigns()->pluck('id');

        if ($campaignIds->isEmpty()) {
            return 0.0;
        }

        $raised = Donation::whereIn('campaign_id', $campaignIds)
            ->completed()
            ->sum('net_amount');

        $withdrawn = $profile->withdrawals()
            ->completed()
            ->sum('amount');

        return (float) max(0, $raised - $withdrawn);
    }

    /**
     * Create a new withdrawal request for an approved organizer.
     *
     * Validates the amount against the configured minimum and the organizer's
     * available balance (under a pessimistic lock to prevent double-spend).
     *
     * @param  array{amount: float, currency?: string, payment_method?: ?string, payment_details?: ?array}  $data
     *
     * @throws ValidationException
     */
    public function request(OrganizerProfile $profile, array $data): Withdrawal
    {
        if (! $profile->isApproved()) {
            throw ValidationException::withMessages([
                'organizer' => 'Only approved organizers may request withdrawals.',
            ]);
        }

        $minimum = $this->feeCalculationService->minimumWithdrawal();
        $amount = (float) $data['amount'];

        if ($amount < $minimum) {
            throw ValidationException::withMessages([
                'amount' => "The minimum withdrawal amount is {$minimum}.",
            ]);
        }

        return DB::transaction(function () use ($profile, $data, $amount) {
            // Lock the profile to prevent concurrent requests overspending the balance.
            $profile = OrganizerProfile::lockForUpdate()->findOrFail($profile->id);

            $available = $this->availableBalance($profile);

            if ($amount > $available) {
                throw ValidationException::withMessages([
                    'amount' => 'The requested amount exceeds your available balance.',
                ]);
            }

            $fees = $this->feeCalculationService->calculateWithdrawalFee($amount);

            $withdrawal = $profile->withdrawals()->create([
                'amount' => $fees['gross_amount'],
                'withdrawal_fee_amount' => $fees['withdrawal_fee_amount'],
                'net_amount' => $fees['net_amount'],
                'currency' => $data['currency'] ?? 'USD',
                'status' => 'pending',
                'payment_method' => $data['payment_method'] ?? null,
                'payment_details' => $data['payment_details'] ?? null,
                'requested_at' => now(),
            ]);

            TyroAudit::log('withdrawal.requested', $withdrawal, null, [
                'organizer_profile_id' => $profile->id,
                'amount' => $fees['gross_amount'],
                'withdrawal_fee_amount' => $fees['withdrawal_fee_amount'],
                'net_amount' => $fees['net_amount'],
            ]);

            return $withdrawal;
        });
    }

    /**
     * Complete a withdrawal — disburses funds and writes the immutable ledger entry.
     *
     * @throws ValidationException
     */
    public function complete(Withdrawal $withdrawal, User $admin): Withdrawal
    {
        if (! in_array($withdrawal->status, ['pending', 'processing'], true)) {
            throw ValidationException::withMessages([
                'withdrawal' => 'Only pending or processing withdrawals can be completed.',
            ]);
        }

        return DB::transaction(function () use ($withdrawal, $admin) {
            $withdrawal = Withdrawal::lockForUpdate()->findOrFail($withdrawal->id);

            $oldStatus = $withdrawal->status;
            $withdrawal->status = 'completed';
            $withdrawal->processed_at = now();
            $withdrawal->completed_at = now();
            $withdrawal->save();

            // Immutable ledger entry — money leaves the platform to the organizer (net of fee).
            Transaction::create([
                'transactionable_type' => Withdrawal::class,
                'transactionable_id' => $withdrawal->id,
                'type' => 'withdrawal',
                'amount' => -$withdrawal->netAmount(),
                'currency' => $withdrawal->currency,
                'description' => "Withdrawal to organizer #{$withdrawal->organizer_profile_id}",
                'meta' => [
                    'organizer_profile_id' => $withdrawal->organizer_profile_id,
                    'gross_amount' => $withdrawal->amount,
                    'withdrawal_fee_amount' => $withdrawal->feeAmount(),
                    'net_amount' => $withdrawal->netAmount(),
                ],
                'created_at' => now(),
            ]);

            TyroAudit::log('withdrawal.completed', $withdrawal, ['status' => $oldStatus], [
                'status' => 'completed',
                'admin_id' => $admin->id,
            ]);

            $withdrawal->organizerProfile->user->notify(new WithdrawalCompleted($withdrawal));

            return $withdrawal;
        });
    }

    /**
     * Mark a withdrawal as failed (e.g. payout rejected by the processor).
     *
     * @throws ValidationException
     */
    public function fail(Withdrawal $withdrawal, User $admin, string $reason): Withdrawal
    {
        if (! in_array($withdrawal->status, ['pending', 'processing'], true)) {
            throw ValidationException::withMessages([
                'withdrawal' => 'Only pending or processing withdrawals can be marked failed.',
            ]);
        }

        return DB::transaction(function () use ($withdrawal, $admin, $reason) {
            $oldStatus = $withdrawal->status;
            $withdrawal->status = 'failed';
            $withdrawal->failed_reason = $reason;
            $withdrawal->processed_at = now();
            $withdrawal->save();

            TyroAudit::log('withdrawal.failed', $withdrawal, ['status' => $oldStatus], [
                'status' => 'failed',
                'admin_id' => $admin->id,
                'failed_reason' => $reason,
            ]);

            $withdrawal->organizerProfile->user->notify(new WithdrawalFailed($withdrawal, $reason));

            return $withdrawal;
        });
    }

    /**
     * Cancel a pending withdrawal (organizer or admin initiated).
     *
     * @throws ValidationException
     */
    public function cancel(Withdrawal $withdrawal): Withdrawal
    {
        if ($withdrawal->status !== 'pending') {
            throw ValidationException::withMessages([
                'withdrawal' => 'Only pending withdrawals can be cancelled.',
            ]);
        }

        $oldStatus = $withdrawal->status;
        $withdrawal->status = 'rejected';
        $withdrawal->save();

        TyroAudit::log('withdrawal.cancelled', $withdrawal, ['status' => $oldStatus], [
            'status' => 'rejected',
        ]);

        return $withdrawal;
    }
}
