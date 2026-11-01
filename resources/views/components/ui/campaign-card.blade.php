@props(['campaign', 'showPct' => false])

@php
    $category = $campaign->category;
    $organizerName = $campaign->organizerProfile?->user?->name;
    $donorsCount = (int) ($campaign->donors_count ?? $campaign->donations_count ?? 0);
    $endDate = $campaign->end_date;
    $daysLeft = null;

    if ($endDate) {
        $daysLeft = (int) now()->startOfDay()->diffInDays($endDate->startOfDay(), false);
    }

    $initial = $organizerName ? Str::upper(Str::substr($organizerName, 0, 1)) : '?';

    // Resolve the cover against the current host (some records predate the
    // storage link and hold absolute URLs). Only render an <img> when the
    // file actually exists on disk, so broken covers never show up.
    $coverUrl = null;
    if ($campaign->cover_image_path) {
        $stored = $campaign->cover_image_path;
        if (Str::startsWith($stored, ['http://', 'https://'])) {
            $coverUrl = $stored;
        } else {
            $diskPath = ltrim($stored, '/');
            if (Storage::disk('public')->exists($diskPath)) {
                $coverUrl = asset('storage/' . $diskPath);
            }
        }
    }
@endphp

<a href="{{ route('campaigns.show', $campaign->slug) }}"
   class="campaign-card"
   aria-label="{{ $campaign->title }}">
    <div class="campaign-card__media">
        @if ($coverUrl)
            <img src="{{ $coverUrl }}"
                 alt=""
                 class="campaign-card__img"
                 loading="lazy"
                 decoding="async">
        @else
            <div class="campaign-card__fallback"><x-ui.icon name="heart" /></div>
        @endif

        <div class="campaign-card__badges">
            @if ($category)
                <span class="campaign-card__badge">{{ $category->name }}</span>
            @endif
            @if ($campaign->is_verified)
                <span class="campaign-card__badge campaign-card__badge--verified">
                    <x-ui.icon name="badge-check" />{{ __('home.card_verified') }}
                </span>
            @endif
        </div>
    </div>

    <div class="campaign-card__body">
        <h3 class="campaign-card__title">{{ $campaign->title }}</h3>

        @if ($campaign->short_description)
            <p class="campaign-card__desc">{{ $campaign->short_description }}</p>
        @endif

        <div class="campaign-card__organizer">
            <span class="campaign-card__avatar" aria-hidden="true">{{ $initial }}</span>
            <span>{{ __('home.card_by') }} <strong>{{ $organizerName ?? '—' }}</strong></span>
        </div>

        <div class="campaign-card__figures">
            <span class="campaign-card__raised">
                <x-ui.money :value="$campaign->raised_amount" :currency="$campaign->currency" :decimals="0" />
            </span>
            <span class="campaign-card__goal">
                {{ __('home.card_raised') }} ·
                {{ __('home.card_goal') }}
                <x-ui.money :value="$campaign->goal_amount" :currency="$campaign->currency" :decimals="0" />
            </span>
            @if ($showPct)
                <span class="campaign-card__pct" aria-label="{{ __('listing.card_pct_funded', ['pct' => (int) round($campaign->progressPercentage())]) }}">
                    {{ (int) round($campaign->progressPercentage()) }}%
                </span>
            @endif
        </div>

        <x-ui.tally :raised="$campaign->raised_amount"
                    :goal="$campaign->goal_amount"
                    :currency="$campaign->currency"
                    :decimals="0"
                    :show-goal="false" />

        <div class="campaign-card__meta">
            <span>{{ trans_choice('home.card_donors', $donorsCount, ['count' => $donorsCount]) }}</span>
            <span class="campaign-card__meta-dot" aria-hidden="true"></span>
            @if ($endDate && $daysLeft !== null && $daysLeft > 0)
                <span>{{ trans_choice('home.card_days_left', $daysLeft, ['count' => $daysLeft]) }}</span>
            @elseif ($endDate)
                <span>{{ __('home.card_ended', ['date' => $endDate->format('M d, Y')]) }}</span>
            @else
                <span>{{ __('home.card_ongoing') }}</span>
            @endif
        </div>
    </div>
</a>
