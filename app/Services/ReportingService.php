<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Models\OrganizerProfile;
use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Aggregates platform-wide financial and operational metrics for the
 * admin reporting dashboard. All money figures are read from completed
 * donation and withdrawal records — the immutable snapshots stored at
 * transaction time.
 */
class ReportingService
{
    /**
     * Compute the full admin reporting summary.
     *
     * @return array{
     *   currency: string,
     *   totals: array<string, float>,
     *   counts: array<string, int>,
     *   pending_withdrawals: array{count: int, amount: float},
     *   recent_donations: Collection,
     *   recent_withdrawals: Collection
     * }
     */
    public function summary(): array
    {
        $currency = Donation::completed()->value('currency') ?? 'USD';

        $donationTotals = Donation::completed()
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as gross')
            ->selectRaw('COALESCE(SUM(platform_fee_amount), 0) as platform_fees')
            ->selectRaw('COALESCE(SUM(payment_processing_fee), 0) as processing_fees')
            ->selectRaw('COALESCE(SUM(net_amount), 0) as net')
            ->selectRaw('COUNT(DISTINCT user_id) as donor_count')
            ->first();

        $withdrawalTotals = Withdrawal::completed()
            ->selectRaw('COALESCE(SUM(amount), 0) as gross')
            ->selectRaw('COALESCE(SUM(withdrawal_fee_amount), 0) as fees')
            ->selectRaw('COALESCE(SUM(net_amount), 0) as net')
            ->first();

        $pendingWithdrawals = Withdrawal::pending()
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(amount), 0) as amount')
            ->first();

        return [
            'currency' => $currency,
            'totals' => [
                'gross_raised' => (float) $donationTotals->gross,
                'platform_fees' => (float) $donationTotals->platform_fees,
                'processing_fees' => (float) $donationTotals->processing_fees,
                'net_to_organizers' => (float) $donationTotals->net,
                'withdrawn_gross' => (float) $withdrawalTotals->gross,
                'withdrawal_fees' => (float) $withdrawalTotals->fees,
                'net_disbursed' => (float) $withdrawalTotals->net,
                'platform_revenue' => (float) $donationTotals->platform_fees + (float) $withdrawalTotals->fees,
            ],
            'counts' => [
                'donors' => (int) $donationTotals->donor_count,
                'campaigns' => Campaign::count(),
                'active_campaigns' => Campaign::active()->count(),
                'organizers_approved' => OrganizerProfile::where('status', 'approved')->count(),
                'organizers_pending' => OrganizerProfile::where('status', 'pending')->count(),
                'donations_completed' => Donation::completed()->count(),
            ],
            'pending_withdrawals' => [
                'count' => (int) $pendingWithdrawals->count,
                'amount' => (float) $pendingWithdrawals->amount,
            ],
            'recent_donations' => Donation::completed()
                ->with(['campaign', 'donor'])
                ->latest('donated_at')
                ->limit(8)
                ->get(),
            'recent_withdrawals' => Withdrawal::with(['organizerProfile.user'])
                ->latest('requested_at')
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * Compute all chart-shaped analytics for the admin dashboard.
     * SVG path data is pre-built here so the view stays presentational.
     *
     * @return array<string, mixed>
     */
    public function charts(): array
    {
        $currency = Donation::completed()->value('currency') ?? 'USD';

        return [
            'currency' => $currency,
            'trend' => $this->donationTrend(14),
            'weekly' => $this->weeklyDonations(),
            'monthly' => $this->monthlyTrend(6),
            'channel_mix' => $this->paymentMethodMix(),
            'status_donut' => $this->campaignStatusDonut(),
            'progress' => $this->topCampaignProgress($currency),
            'top_categories' => $this->topCategories($currency),
            'periods' => $this->periodReports($currency),
        ];
    }

    /**
     * Daily gross donation amounts for the trailing N days, with smooth
     * SVG line and area paths for a 600x180 viewBox.
     *
     * @return array{
     *   labels: array<string>,
     *   total: float,
     *   growth_pct: float,
     *   values: array<int, float>,
     *   line_path: string,
     *   area_path: string,
     *   range_start: string,
     *   range_end: string
     * }
     */
    private function donationTrend(int $days): array
    {
        $dates = collect(range($days - 1, 0))->map(fn (int $offset) => now()->subDays($offset)->toDateString());

        $rows = Donation::completed()
            ->where('donated_at', '>=', $dates->first().' 00:00:00')
            ->selectRaw('DATE(donated_at) as day')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $values = $dates->map(fn (string $date) => (float) ($rows[$date] ?? 0))->values();

        $max = max(1.0, $values->max());
        $count = $values->count();
        $xs = $count > 1
            ? $values->keys()->map(fn (int $i) => round(($i / ($count - 1)) * 600, 1))->all()
            : [0];
        $ys = $values->map(fn (float $v) => round(160 - (($v / $max) * 130), 1))->all();

        $line = $this->smoothPath($xs, $ys);
        $area = $line.' L 600 160 L 0 160 Z';

        $first = (float) ($rows[$dates->first()] ?? 0);
        $last = (float) ($rows[$dates->last()] ?? 0);
        $growth = $first > 0 ? round((($last - $first) / $first) * 100, 1) : 0.0;

        return [
            'labels' => $dates->map(fn (string $date) => Carbon::parse($date)->format('M j'))->all(),
            'total' => round($values->sum(), 2),
            'growth_pct' => $growth,
            'values' => $values->all(),
            'line_path' => $line,
            'area_path' => $area,
            'range_start' => Carbon::parse($dates->first())->format('M j'),
            'range_end' => Carbon::parse($dates->last())->format('M j'),
        ];
    }

    /**
     * Gross donations for each of the last seven days, as vertical bars.
     *
     * @return array<int, array{label: string, value: int, pct: int}>
     */
    private function weeklyDonations(): array
    {
        $dates = collect(range(6, 0))->map(fn (int $offset) => now()->subDays($offset));

        $rows = Donation::completed()
            ->where('donated_at', '>=', $dates->first()->toDateString().' 00:00:00')
            ->selectRaw('DATE(donated_at) as day')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $values = $dates->map(fn (Carbon $date) => (float) ($rows[$date->toDateString()] ?? 0))->values();
        $max = max(1.0, $values->max());

        return $dates->map(function (Carbon $date, int $index) use ($rows, $max): array {
            $value = (int) round((float) ($rows[$date->toDateString()] ?? 0));

            return [
                'label' => $date->format('D'),
                'value' => $value,
                'pct' => (int) round(($value / $max) * 100),
                'key' => $index,
            ];
        })->all();
    }

    /**
     * Gross donations per calendar month for the trailing N months,
     * with a smooth area path.
     *
     * @return array{
     *   labels: array<string>,
     *   total: float,
     *   values: array<int, float>,
     *   area_path: string,
     *   range_start: string,
     *   range_end: string
     * }
     */
    private function monthlyTrend(int $months): array
    {
        $starts = collect(range($months - 1, 0))->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));

        $rows = Donation::completed()
            ->where('donated_at', '>=', $starts->first()->toDateTimeString())
            ->selectRaw("DATE_FORMAT(donated_at, '%Y-%m') as month")
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $values = $starts->map(fn (Carbon $date) => (float) ($rows[$date->format('Y-m')] ?? 0))->values();

        $max = max(1.0, $values->max());
        $count = $values->count();
        $xs = $count > 1
            ? $values->keys()->map(fn (int $i) => round(($i / ($count - 1)) * 600, 1))->all()
            : [0];
        $ys = $values->map(fn (float $v) => round(160 - (($v / $max) * 130), 1))->all();

        $line = $this->smoothPath($xs, $ys);

        return [
            'labels' => $starts->map(fn (Carbon $date) => $date->format('M'))->all(),
            'total' => round($values->sum(), 2),
            'values' => $values->all(),
            'area_path' => $line.' L 600 160 L 0 160 Z',
            'range_start' => $starts->first()->format('M Y'),
            'range_end' => $starts->last()->format('M Y'),
        ];
    }

    /**
     * Share of completed donations grouped by payment method.
     *
     * @return array<int, array{
     *   label: string,
     *   total: float,
     *   count: int,
     *   pct: float,
     *   color: string
     * }>
     */
    private function paymentMethodMix(): array
    {
        $palette = ['var(--primary)', 'var(--info)', 'var(--success)', 'var(--warning)', 'var(--destructive)'];

        $rows = Donation::completed()
            ->selectRaw("COALESCE(NULLIF(TRIM(payment_method), ''), 'Other') as method")
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        $grandTotal = max(0.0, (float) $rows->sum('total'));

        return $rows->map(function ($row, int $index) use ($grandTotal, $palette): array {
            $total = (float) $row->total;

            return [
                'label' => ucfirst(strtolower((string) $row->method)),
                'total' => round($total, 2),
                'count' => (int) $row->count,
                'pct' => $grandTotal > 0 ? round(($total / $grandTotal) * 100, 1) : 0.0,
                'color' => $palette[$index % count($palette)],
            ];
        })->all();
    }

    /**
     * Campaign count by status, shaped for a donut chart.
     *
     * @return array{
     *   total: int,
     *   slices: array<int, array{label: string, count: int, pct: int, color: string}>
     * }
     */
    private function campaignStatusDonut(): array
    {
        $order = ['active', 'pending', 'completed', 'paused', 'draft', 'rejected'];
        $colors = [
            'active' => 'var(--success)',
            'pending' => 'var(--warning)',
            'completed' => 'var(--primary)',
            'paused' => 'var(--info)',
            'draft' => 'var(--muted-foreground)',
            'rejected' => 'var(--destructive)',
        ];

        $rows = Campaign::select('status')->selectRaw('COUNT(*) as count')->groupBy('status')->pluck('count', 'status');
        $total = (int) $rows->sum();

        $slices = collect($order)
            ->filter(fn (string $status) => ($rows[$status] ?? 0) > 0)
            ->map(function (string $status) use ($rows, $total, $colors): array {
                $count = (int) $rows[$status];

                return [
                    'label' => ucfirst($status),
                    'count' => $count,
                    'pct' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
                    'color' => $colors[$status],
                ];
            })
            ->all();

        // Guarantee a single 100% slice even when empty.
        if ($slices === []) {
            $slices = [['label' => 'No campaigns', 'count' => 0, 'pct' => 100, 'color' => 'var(--muted-foreground)']];
        }

        return ['total' => $total, 'slices' => $slices];
    }

    /**
     * Top campaigns ordered by fundraising progress.
     *
     * @return array<int, array{
     *   title: string,
     *   subtitle: string,
     *   pct: int,
     *   meta: string,
     *   badge_class: string,
     *   badge_text: string,
     *   bar_color: string
     * }>
     */
    private function topCampaignProgress(string $currency): array
    {
        $campaigns = Campaign::publiclyVisible()
            ->withCount('donations')
            ->orderByDesc('raised_amount')
            ->limit(50)
            ->get()
            ->filter(fn (Campaign $campaign) => (float) $campaign->goal_amount > 0)
            ->map(function (Campaign $campaign) use ($currency): array {
                $pct = (int) $campaign->progressPercentage();

                return [
                    'title' => $campaign->title,
                    'subtitle' => sprintf(
                        '%s %s raised of %s · %d donations',
                        $currency,
                        number_format((float) $campaign->raised_amount, 0),
                        number_format((float) $campaign->goal_amount, 0),
                        (int) $campaign->donations_count
                    ),
                    'pct' => $pct,
                    'meta' => $pct >= 100 ? 'Goal reached' : ($currency.' '.number_format(max(0, (float) $campaign->goal_amount - (float) $campaign->raised_amount), 0).' to go'),
                    'badge_class' => $pct >= 100 ? 'badge-success' : ($pct >= 50 ? 'badge-primary' : 'badge-warning'),
                    'badge_text' => $pct >= 100 ? 'Goal met' : ($pct >= 50 ? 'On track' : 'Building'),
                    'bar_color' => $pct >= 100 ? 'var(--success)' : 'var(--primary)',
                ];
            })
            ->sortByDesc('pct')
            ->take(5)
            ->values()
            ->all();

        if ($campaigns === []) {
            $campaigns = [[
                'title' => 'No active campaigns yet',
                'subtitle' => 'Start one to see progress here',
                'pct' => 0,
                'meta' => '—',
                'badge_class' => 'badge-secondary',
                'badge_text' => 'Empty',
                'bar_color' => 'var(--muted-foreground)',
            ]];
        }

        return $campaigns;
    }

    /**
     * Top categories by gross raised, shaped for horizontal bars.
     *
     * @return array<int, array{label: string, value: float, pct: int, color: string}>
     */
    private function topCategories(string $currency): array
    {
        $palette = ['var(--primary)', 'var(--info)', 'var(--success)', 'var(--warning)', 'var(--destructive)'];

        $rows = CampaignCategory::active()
            ->withSum(['campaigns as raised_sum' => fn ($query) => $query->whereIn('status', ['active', 'completed', 'paused'])], 'raised_amount')
            ->orderByDesc('raised_sum')
            ->limit(6)
            ->get()
            ->filter(fn (CampaignCategory $category) => (float) ($category->raised_sum ?? 0) > 0);

        $max = max(1.0, (float) $rows->max('raised_sum'));

        return $rows->map(function (CampaignCategory $category, int $index) use ($max, $palette): array {
            $value = (float) ($category->raised_sum ?? 0);

            return [
                'label' => $category->name,
                'value' => round($value, 2),
                'pct' => (int) round(($value / $max) * 100),
                'color' => $palette[$index % count($palette)],
            ];
        })->values()->all();
    }

    /**
     * Donation totals for today, this week, this month, and last month.
     *
     * @return array<string, array{label: string, amount: float, count: int}>
     */
    private function periodReports(string $currency): array
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateTimeString();
        $monthStart = now()->startOfMonth()->toDateTimeString();
        $lastMonthStart = now()->startOfMonth()->subMonth();
        $lastMonthEnd = now()->startOfMonth();

        $for = fn (string $column, string $start, ?string $end = null) => Donation::completed()
            ->where('donated_at', '>=', $start)
            ->when($end !== null, fn ($query) => $query->where('donated_at', '<', $end))
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total')
            ->selectRaw('COUNT(*) as count')
            ->first();

        $rows = [
            'today' => $for('donated_at', $today.' 00:00:00'),
            'week' => $for('donated_at', $weekStart),
            'month' => $for('donated_at', $monthStart),
            'last_month' => $for('donated_at', $lastMonthStart->toDateTimeString(), $lastMonthEnd->toDateTimeString()),
        ];

        return [
            'today' => ['label' => 'Today', 'amount' => (float) $rows['today']->total, 'count' => (int) $rows['today']->count],
            'week' => ['label' => 'This week', 'amount' => (float) $rows['week']->total, 'count' => (int) $rows['week']->count],
            'month' => ['label' => 'This month', 'amount' => (float) $rows['month']->total, 'count' => (int) $rows['month']->count],
            'last_month' => ['label' => 'Last month', 'amount' => (float) $rows['last_month']->total, 'count' => (int) $rows['last_month']->count],
        ];
    }

    /**
     * Build a smooth cubic-bezier path through the given x/y points.
     *
     * @param  array<int, float>  $xs
     * @param  array<int, float>  $ys
     */
    private function smoothPath(array $xs, array $ys): string
    {
        $n = count($xs);
        if ($n === 0) {
            return 'M 0 160';
        }

        if ($n === 1) {
            return "M {$xs[0]} {$ys[0]}";
        }

        $d = "M {$xs[0]} {$ys[0]}";
        for ($i = 0; $i < $n - 1; $i++) {
            $p0x = $xs[max(0, $i - 1)];
            $p0y = $ys[max(0, $i - 1)];
            $p1x = $xs[$i];
            $p1y = $ys[$i];
            $p2x = $xs[$i + 1];
            $p2y = $ys[$i + 1];
            $p3x = $xs[min($n - 1, $i + 2)];
            $p3y = $ys[min($n - 1, $i + 2)];

            $c1x = $p1x + (($p2x - $p0x) / 6);
            $c1y = $p1y + (($p2y - $p0y) / 6);
            $c2x = $p2x - (($p3x - $p1x) / 6);
            $c2y = $p2y - (($p3y - $p1y) / 6);

            $d .= sprintf(' C %.1f %.1f, %.1f %.1f, %.1f %.1f', $c1x, $c1y, $c2x, $c2y, $p2x, $p2y);
        }

        return $d;
    }
}
