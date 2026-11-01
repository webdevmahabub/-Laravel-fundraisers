@props([
    'campaign',
    'delay' => 0,
])

<a href="{{ route('campaigns.show', $campaign->slug) }}" {{ $attributes->merge(['class' => 'ledger__row']) }}>
    <span class="ledger__media">
        @if ($campaign->cover_image_path)
            <img src="{{ Storage::disk('public')->url($campaign->cover_image_path) }}"
                 alt="{{ $campaign->title }}" loading="lazy">
        @else
            {{ mb_substr($campaign->title, 0, 1) }}
        @endif
    </span>

    <span class="ledger__main">
        <span class="ledger__title">{{ $campaign->title }}</span>
        <span class="ledger__meta">
            @if ($campaign->category)
                <span class="tag">
                    <span class="tag__icon">{{ $campaign->category->icon }}</span>
                    {{ $campaign->category->name }}
                </span>
            @endif
            <span>{{ number_format($campaign->donations_count ?? 0) }} {{ Str::plural('donor', $campaign->donations_count ?? 0) }}</span>
            @if ($campaign->end_date)
                <span>ends {{ $campaign->end_date->diffForHumans() }}</span>
            @endif
            @if ($campaign->is_verified)
                <span class="verified-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Verified
                </span>
            @endif
        </span>
    </span>

    <span class="ledger__aside">
        <x-ui.tally
            :raised="$campaign->raised_amount"
            :goal="$campaign->goal_amount"
            :currency="$campaign->currency"
            :delay="$delay"
        />
    </span>
</a>
