@extends('layouts.public')

@section('title', __('listing.meta_title'))
@section('description', __('listing.meta_description'))

@php
    $f = $filters;

    // Merge an override into the current query string, dropping empties.
    $qs = fn (array $overrides) => array_filter(array_merge([
        'search' => $f['search'],
        'category' => $f['category'],
        'status' => $f['status'],
        'verified' => $f['verified'] ? '1' : null,
        'progress' => $f['progress'],
        'sort' => $f['sort'] !== 'newest' ? $f['sort'] : null,
    ], $overrides), fn ($v) => $v !== null && $v !== '');

    // Removable active-filter chips (label + query string without that filter).
    $chips = [];
    if ($f['search']) {
        $chips[] = ['label' => $f['search'], 'remove' => $qs(['search' => null])];
    }
    if ($selectedCategory) {
        $chips[] = ['label' => $selectedCategory->name, 'remove' => $qs(['category' => null])];
    }
    if ($f['status']) {
        $chips[] = ['label' => __('listing.status_' . $f['status']), 'remove' => $qs(['status' => null])];
    }
    if ($f['verified']) {
        $chips[] = ['label' => __('listing.verified_only'), 'remove' => $qs(['verified' => null])];
    }
    if ($f['progress']) {
        $chips[] = ['label' => __('listing.progress_' . $f['progress']), 'remove' => $qs(['progress' => null])];
    }
@endphp

@section('content')

{{-- ------------------------------------------------------------ BREADCRUMB --}}
<nav class="wrap breadcrumb breadcrumb--page" aria-label="Breadcrumb">
    <a href="{{ url('/') }}">{{ __('listing.breadcrumb_home') }}</a>
    <span class="breadcrumb-sep" aria-hidden="true">/</span>
    <span aria-current="page">{{ __('listing.title') }}</span>
</nav>

{{-- ---------------------------------------------------------------- HEADER --}}
<section class="wrap page-head">
    <div class="page-head__copy">
        <p class="kicker">{{ __('listing.kicker') }}</p>
        <h1 class="h1 page-head__title">{{ __('listing.title') }}</h1>
        <p class="section__note page-head__lede">{{ __('listing.lede') }}</p>
        <p class="meta page-head__meta">
            {{ trans_choice('listing.total_campaigns', $totalPublicCount, ['count' => number_format($totalPublicCount)]) }}
        </p>
    </div>

    <a href="{{ route('organizer.campaigns.create') }}" class="btn btn-lg page-head__cta">
        <x-ui.icon name="plus" :size="18" />
        {{ __('listing.start_campaign') }}
    </a>
</section>

{{-- ---------------------------------------------------------------- SEARCH --}}
<section class="wrap searchbar-zone" aria-label="{{ __('listing.search_label') }}">
    <form method="GET" action="{{ route('campaigns.index') }}" class="searchbar" role="search">
        @if ($f['category'])
            <input type="hidden" name="category" value="{{ $f['category'] }}">
        @endif
        @if ($f['status'])
            <input type="hidden" name="status" value="{{ $f['status'] }}">
        @endif
        @if ($f['verified'])
            <input type="hidden" name="verified" value="1">
        @endif
        @if ($f['progress'])
            <input type="hidden" name="progress" value="{{ $f['progress'] }}">
        @endif
        @if ($f['sort'] !== 'newest')
            <input type="hidden" name="sort" value="{{ $f['sort'] }}">
        @endif

        <svg class="searchbar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>

        <label class="sr-only" for="campaign-search">{{ __('listing.search_label') }}</label>
        <input type="search" id="campaign-search" name="search"
               class="searchbar__input"
               placeholder="{{ __('listing.search_placeholder') }}"
               value="{{ $f['search'] }}"
               autocomplete="off">

        @if ($f['search'])
            <a href="{{ route('campaigns.index', $qs(['search' => null])) }}" class="searchbar__clear">
                <x-ui.icon name="x" :size="14" />
                <span>{{ __('listing.clear_search') }}</span>
            </a>
        @endif

        <button type="submit" class="btn searchbar__btn">{{ __('listing.search_btn') }}</button>
    </form>
</section>

{{-- ---------------------------------------------------------------- LISTING --}}
<section class="wrap listing">
    <button type="button" class="btn btn-secondary filters-toggle" id="filters-toggle"
            aria-expanded="false" aria-controls="filters-panel">
        <x-ui.icon name="sliders-horizontal" :size="16" />
        {{ __('listing.filters_btn') }}
        @if (count($chips))
            <span class="filters-toggle__count" aria-hidden="true">{{ count($chips) }}</span>
        @endif
    </button>

    <div class="listing__grid">
        {{-- -------------------------------------------------------- FILTERS --}}
        <aside class="filters" id="filters-panel" aria-label="{{ __('listing.filters_title') }}">
            <div class="filters__head">
                <p class="filters__title">{{ __('listing.filters_title') }}</p>
                <button type="button" class="filters__close" id="filters-close" aria-label="{{ __('listing.filters_close') }}">
                    <x-ui.icon name="x" :size="18" />
                </button>
            </div>

            <form method="GET" action="{{ route('campaigns.index') }}" class="filters__form">
                @if ($f['search'])
                    <input type="hidden" name="search" value="{{ $f['search'] }}">
                @endif
                @if ($f['sort'] !== 'newest')
                    <input type="hidden" name="sort" value="{{ $f['sort'] }}">
                @endif

                {{-- Category ------------------------------------------------}}
                <fieldset class="filters__group">
                    <legend class="filters__label">{{ __('listing.filter_category') }}</legend>

                    <label class="choice">
                        <input type="radio" name="category" value="" data-autosubmit @checked(! $f['category'])>
                        <span class="choice__label">{{ __('listing.all_categories') }}</span>
                        <span class="choice__count">{{ number_format($totalPublicCount) }}</span>
                    </label>

                    @foreach ($categories as $category)
                        <label class="choice">
                            <input type="radio" name="category" value="{{ $category->slug }}"
                                   data-autosubmit @checked($f['category'] === $category->slug)>
                            <span class="choice__label">{{ $category->name }}</span>
                            <span class="choice__count">{{ number_format($category->campaigns_count ?? 0) }}</span>
                        </label>
                    @endforeach
                </fieldset>

                {{-- Status --------------------------------------------------}}
                <fieldset class="filters__group">
                    <legend class="filters__label">{{ __('listing.filter_status') }}</legend>

                    <label class="choice">
                        <input type="radio" name="status" value="" data-autosubmit @checked(! $f['status'])>
                        <span class="choice__label">{{ __('listing.status_any') }}</span>
                    </label>
                    <label class="choice">
                        <input type="radio" name="status" value="active" data-autosubmit @checked($f['status'] === 'active')>
                        <span class="choice__label">{{ __('listing.status_active') }}</span>
                    </label>
                    <label class="choice">
                        <input type="radio" name="status" value="completed" data-autosubmit @checked($f['status'] === 'completed')>
                        <span class="choice__label">{{ __('listing.status_completed') }}</span>
                    </label>
                    <label class="choice choice--check">
                        <input type="checkbox" name="verified" value="1" data-autosubmit @checked($f['verified'])>
                        <span class="choice__label">{{ __('listing.verified_only') }}</span>
                    </label>
                </fieldset>

                {{-- Progress -------------------------------------------------}}
                <fieldset class="filters__group">
                    <legend class="filters__label">{{ __('listing.filter_progress') }}</legend>

                    @foreach ([
                        '' => 'progress_any',
                        'just_started' => 'progress_just_started',
                        '25' => 'progress_25',
                        '50' => 'progress_50',
                        '75' => 'progress_75',
                        'fully_funded' => 'progress_fully_funded',
                    ] as $value => $key)
                        <label class="choice">
                            <input type="radio" name="progress" value="{{ $value }}"
                                   data-autosubmit @checked(($f['progress'] ?? '') === $value)>
                            <span class="choice__label">{{ __('listing.' . $key) }}</span>
                        </label>
                    @endforeach
                </fieldset>

                @if ($hasActiveFilters)
                    <a href="{{ route('campaigns.index') }}" class="filters__reset">
                        <x-ui.icon name="x" :size="14" />
                        {{ __('listing.reset_filters') }}
                    </a>
                @endif
            </form>
        </aside>

        {{-- -------------------------------------------------------- RESULTS --}}
        <div class="results">
            <div class="results__head">
                <p class="results__count">
                    <span class="money">{{ number_format($campaigns->total()) }}</span>
                    {{ trans_choice('listing.results_count', $campaigns->total(), ['count' => $campaigns->total()]) }}
                    <span class="muted">&middot; {{ __('listing.results_of', ['count' => number_format($totalPublicCount)]) }}</span>
                </p>

                <form method="GET" action="{{ route('campaigns.index') }}" class="sortbar">
                    @if ($f['search'])
                        <input type="hidden" name="search" value="{{ $f['search'] }}">
                    @endif
                    @if ($f['category'])
                        <input type="hidden" name="category" value="{{ $f['category'] }}">
                    @endif
                    @if ($f['status'])
                        <input type="hidden" name="status" value="{{ $f['status'] }}">
                    @endif
                    @if ($f['verified'])
                        <input type="hidden" name="verified" value="1">
                    @endif
                    @if ($f['progress'])
                        <input type="hidden" name="progress" value="{{ $f['progress'] }}">
                    @endif

                    <label class="sortbar__label" for="sort-select">{{ __('listing.sort_label') }}</label>
                    <select id="sort-select" name="sort" class="sortbar__select" data-autosubmit>
                        @foreach ([
                            'newest' => 'sort_newest',
                            'most_funded' => 'sort_most_funded',
                            'ending_soon' => 'sort_ending_soon',
                            'most_donated' => 'sort_most_donated',
                            'recently_updated' => 'sort_recently_updated',
                        ] as $value => $key)
                            <option value="{{ $value }}" @selected($f['sort'] === $value)>
                                {{ __('listing.' . $key) }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if ($chips)
                <div class="chips" aria-label="{{ __('listing.active_filters') }}">
                    @foreach ($chips as $chip)
                        <a href="{{ route('campaigns.index', $chip['remove']) }}" class="chip">
                            <span class="chip__label">{{ $chip['label'] }}</span>
                            <span class="chip__x" aria-hidden="true"><x-ui.icon name="x" :size="12" /></span>
                            <span class="sr-only">{{ __('listing.chip_remove', ['label' => $chip['label']]) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($campaigns->count())
                <div class="campaign-grid">
                    @foreach ($campaigns as $campaign)
                        <x-ui.campaign-card :campaign="$campaign" :show-pct="true" />
                    @endforeach
                </div>

                {{ $campaigns->links('pagination::ledger') }}
            @else
                <div class="empty-state listing-empty">
                    <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="empty-state-title">{{ __('listing.empty_title') }}</p>
                    <p class="empty-state-description">{{ __('listing.empty_text') }}</p>
                    <div class="empty-state__actions">
                        @if ($hasActiveFilters)
                            <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">{{ __('listing.empty_clear') }}</a>
                        @endif
                        <a href="{{ route('campaigns.index') }}" class="btn btn-primary">{{ __('listing.empty_browse') }}</a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="filters-overlay" id="filters-overlay" aria-hidden="true"></div>
</section>

@endsection

@push('scripts')
<script>
(function () {
    // Filter controls apply immediately.
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () {
            if (el.form) el.form.submit();
        });
    });

    // Mobile filter drawer.
    var toggle = document.getElementById('filters-toggle');
    var panel = document.getElementById('filters-panel');
    var close = document.getElementById('filters-close');
    var overlay = document.getElementById('filters-overlay');
    if (!toggle || !panel || !close) return;

    function setOpen(open) {
        panel.classList.toggle('is-open', open);
        overlay.classList.toggle('is-visible', open);
        document.body.classList.toggle('has-drawer', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
        if (open) close.focus(); else toggle.focus();
    }

    toggle.addEventListener('click', function () { setOpen(true); });
    close.addEventListener('click', function () { setOpen(false); });
    if (overlay) overlay.addEventListener('click', function () { setOpen(false); });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panel.classList.contains('is-open')) {
            setOpen(false);
        }
    });
})();
</script>
@endpush
