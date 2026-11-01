@extends('layouts.public')

@section('title', __('home.meta_title', ['app' => config('app.name')]))

@php
    // Lucide icon per category slug — presentation mapping only, no queries.
    $categoryIcons = [
        'medical-health' => 'activity',
        'education' => 'graduation-cap',
        'emergency-relief' => 'life-buoy',
        'community-nonprofits' => 'users',
        'animals-pets' => 'paw-print',
        'environment' => 'leaf',
        'creative-projects' => 'palette',
        'sports-recreation' => 'trophy',
        'business-startups' => 'briefcase',
        'family-children' => 'baby',
        'memorials-funerals' => 'flame',
        'other' => 'sparkles',
    ];

    $storyPct = $successStory && $successStory->goal_amount > 0
        ? min(100, (int) round(($successStory->raised_amount / $successStory->goal_amount) * 100))
        : 0;
@endphp

@section('content')

{{-- ============================ HERO ============================ --}}
<section class="hero" aria-labelledby="hero-title">
    <div class="wrap hero__grid">
        <div class="hero__copy">
            <p class="kicker">{{ __('home.hero_kicker') }}</p>
            <h1 id="hero-title" class="hero__title">{{ __('home.hero_title') }}</h1>
            <p class="hero__lede">{{ __('home.hero_lede') }}</p>

            <div class="hero__cta">
                <a href="{{ route('organizer.campaigns.create') }}" class="btn btn-primary btn-lg">
                    {{ __('home.hero_cta_start') }}
                    <x-ui.icon name="arrow-right" />
                </a>
                <a href="{{ route('campaigns.index') }}" class="btn btn-secondary btn-lg">
                    {{ __('home.hero_cta_explore') }}
                </a>
            </div>

            <p class="hero__trust">
                <x-ui.icon name="receipt-text" />
                {{ __('home.hero_trust') }}
            </p>
        </div>

        <div class="hero__media">
            <img src="{{ asset('images/hero-clinic.jpg') }}"
                 alt="{{ __('home.hero_photo_alt') }}"
                 class="hero__photo"
                 width="1024" height="1280"
                 fetchpriority="high"
                 decoding="async">

            @if ($successStory)
                <div class="hero__receipt" aria-hidden="true">
                    <div class="hero__receipt-label">
                        <span class="pulse"></span>{{ __('home.hero_receipt_label') }}
                    </div>
                    <div class="hero__receipt-title">{{ $successStory->title }}</div>
                    <x-ui.tally :raised="$successStory->raised_amount"
                                :goal="$successStory->goal_amount"
                                :currency="$successStory->currency"
                                :decimals="0"
                                :show-goal="false" />
                    <div class="hero__receipt-figures">
                        <span>{{ $storyPct }}% {{ __('home.hero_receipt_funded') }}</span>
                        <span>{{ $successStory->donations_count }} {{ __('home.hero_receipt_donors') }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- ====================== LIVE STATISTICS ====================== --}}
<section class="stats-band" aria-label="{{ __('home.stats_label') }}">
    <div class="wrap stats-band__grid">
        <div class="stats-band__item">
            <div class="stats-band__value">
                @if ($totalRaisedByCurrency->isEmpty())
                    0
                @else
                    @foreach ($totalRaisedByCurrency as $currency => $currencyTotal)
                        @if (!$loop->first) · @endif
                        <x-ui.money :value="$currencyTotal" :currency="$currency" :decimals="0" />
                    @endforeach
                @endif
            </div>
            <div class="stats-band__label">{{ __('home.stat_raised') }}</div>
        </div>
        <div class="stats-band__item">
            <div class="stats-band__value">{{ number_format($totalCampaigns) }}</div>
            <div class="stats-band__label">{{ __('home.stat_active') }}</div>
        </div>
        <div class="stats-band__item">
            <div class="stats-band__value">{{ number_format($totalDonors) }}</div>
            <div class="stats-band__label">{{ __('home.stat_donors') }}</div>
        </div>
        <div class="stats-band__item">
            <div class="stats-band__value">{{ number_format($verifiedCampaigns) }}</div>
            <div class="stats-band__label">{{ __('home.stat_verified') }}</div>
        </div>
    </div>
</section>

{{-- ===================== FEATURED CAMPAIGNS ===================== --}}
<section class="section" id="campaigns" aria-labelledby="campaigns-title">
    <div class="wrap">
        <div class="section__head">
            <div>
                <p class="kicker">{{ __('home.featured_kicker') }}</p>
                <h2 id="campaigns-title" class="h2">{{ __('home.featured_title') }}</h2>
                <p class="section__note">{{ __('home.featured_lede') }}</p>
            </div>
            <a href="{{ route('campaigns.index') }}" class="btn btn-secondary btn-sm">
                {{ __('home.view_all') }}
                <x-ui.icon name="arrow-right" />
            </a>
        </div>

        <div class="campaign-grid">
            @foreach ($featuredCampaigns as $campaign)
                <x-ui.campaign-card :campaign="$campaign" />
            @endforeach

            {{-- When few campaigns exist, the last slot invites the visitor to
                 start one — an honest prompt, never a fabricated listing. --}}
            @if ($featuredCampaigns->count() < 3)
                <a href="{{ route('organizer.campaigns.create') }}" class="campaign-card campaign-card--cta">
                    <span class="cta-icon"><x-ui.icon name="plus" /></span>
                    <span class="campaign-card--cta-title">{{ __('home.card_start_cta') }}</span>
                    <span class="campaign-card--cta-text">{{ __('home.card_start_cta_text') }}</span>
                </a>
            @endif
        </div>
    </div>
</section>

{{-- ========================= CATEGORIES ========================= --}}
<section class="section section--tint" id="categories" aria-labelledby="categories-title">
    <div class="wrap">
        <div class="section__head">
            <div>
                <p class="kicker">{{ __('home.categories_kicker') }}</p>
                <h2 id="categories-title" class="h2">{{ __('home.categories_title') }}</h2>
                <p class="section__note">{{ __('home.categories_lede') }}</p>
            </div>
            <a href="{{ route('campaigns.index') }}" class="btn btn-secondary btn-sm">
                {{ __('home.categories_view_all') }}
                <x-ui.icon name="arrow-right" />
            </a>
        </div>

        <div class="category-grid">
            @foreach ($categories as $category)
                <a href="{{ route('campaigns.index', ['category' => $category->slug]) }}" class="category-tile">
                    <span class="category-tile__icon">
                        <x-ui.icon :name="$categoryIcons[$category->slug] ?? 'sparkles'" />
                    </span>
                    <span class="category-tile__name">{{ $category->name }}</span>
                    <span class="category-tile__count">{{ trans_choice('home.category_count', $category->campaigns_count, ['count' => $category->campaigns_count]) }}</span>
                    <x-ui.icon name="arrow-right" class="category-tile__arrow" />
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ========================= HOW IT WORKS ========================= --}}
<section class="section" id="how-it-works" aria-labelledby="steps-title">
    <div class="wrap">
        <div class="section__head">
            <div>
                <p class="kicker">{{ __('home.steps_kicker') }}</p>
                <h2 id="steps-title" class="h2">{{ __('home.steps_title') }}</h2>
            </div>
        </div>

        <ol class="steps">
            <li class="step">
                <span class="step__icon"><x-ui.icon name="pen-line" /></span>
                <h3 class="step__title">{{ __('home.step1_title') }}</h3>
                <p class="step__text">{{ __('home.step1_text') }}</p>
            </li>
            <li class="step">
                <span class="step__icon"><x-ui.icon name="share-2" /></span>
                <h3 class="step__title">{{ __('home.step2_title') }}</h3>
                <p class="step__text">{{ __('home.step2_text') }}</p>
            </li>
            <li class="step">
                <span class="step__icon"><x-ui.icon name="hand-coins" /></span>
                <h3 class="step__title">{{ __('home.step3_title') }}</h3>
                <p class="step__text">{{ __('home.step3_text') }}</p>
            </li>
        </ol>

        <div style="margin-top:2rem;">
            <a href="{{ route('organizer.campaigns.create') }}" class="btn btn-primary">
                {{ __('home.steps_cta') }}
                <x-ui.icon name="arrow-right" />
            </a>
        </div>
    </div>
</section>

{{-- ========================= TRUST ========================= --}}
<section class="section section--tint" id="trust" aria-labelledby="trust-title">
    <div class="wrap">
        <div class="section__head">
            <div>
                <p class="kicker">{{ __('home.trust_kicker') }}</p>
                <h2 id="trust-title" class="h2">{{ __('home.trust_title') }}</h2>
            </div>
        </div>

        <div class="trust-grid">
            <div class="trust-item">
                <span class="trust-item__icon"><x-ui.icon name="shield-check" /></span>
                <h3 class="trust-item__title">{{ __('home.trust1_title') }}</h3>
                <p class="trust-item__text">{{ __('home.trust1_text') }}</p>
            </div>
            <div class="trust-item">
                <span class="trust-item__icon"><x-ui.icon name="clipboard-check" /></span>
                <h3 class="trust-item__title">{{ __('home.trust2_title') }}</h3>
                <p class="trust-item__text">{{ __('home.trust2_text') }}</p>
            </div>
            <div class="trust-item">
                <span class="trust-item__icon"><x-ui.icon name="receipt-text" /></span>
                <h3 class="trust-item__title">{{ __('home.trust3_title') }}</h3>
                <p class="trust-item__text">{{ __('home.trust3_text') }}</p>
            </div>
            <div class="trust-item">
                <span class="trust-item__icon"><x-ui.icon name="percent" /></span>
                <h3 class="trust-item__title">{{ __('home.trust4_title') }}</h3>
                <p class="trust-item__text">{{ __('home.trust4_text') }}</p>
            </div>
        </div>

        <div class="trust-note">
            <x-ui.icon name="info" />
            <span>{{ __('home.trust_note') }}</span>
        </div>
    </div>
</section>

{{-- ======================= SUCCESS STORY ======================= --}}
@if ($successStory)
<section class="section" id="stories" aria-labelledby="story-title">
    <div class="wrap">
        <div class="section__head">
            <div>
                <p class="kicker">{{ __('home.story_kicker') }}</p>
                <h2 id="story-title" class="h2">{{ __('home.story_title') }}</h2>
            </div>
        </div>

        <div class="story-card">
            <div class="story-card__media">
                @php
                    $storyCover = null;
                    if ($successStory->cover_image_path) {
                        $storyStored = $successStory->cover_image_path;
                        if (Str::startsWith($storyStored, ['http://', 'https://'])) {
                            $storyCover = $storyStored;
                        } else {
                            $storyDiskPath = ltrim($storyStored, '/');
                            if (Storage::disk('public')->exists($storyDiskPath)) {
                                $storyCover = asset('storage/' . $storyDiskPath);
                            }
                        }
                    }
                @endphp
                @if ($storyCover)
                    <img src="{{ $storyCover }}"
                         alt="{{ $successStory->title }}"
                         loading="lazy" decoding="async">
                @else
                    <div class="campaign-card__fallback"><x-ui.icon name="heart" /></div>
                @endif
            </div>

            <div class="story-card__body">
                <div class="story-card__badges">
                    @if ($successStory->category)
                        <span class="badge badge-green">{{ $successStory->category->name }}</span>
                    @endif
                    @if ($successStory->is_verified)
                        <span class="verified-badge">
                            <x-ui.icon name="badge-check" />{{ __('home.card_verified') }}
                        </span>
                    @endif
                </div>

                <h3 class="story-card__title">{{ $successStory->title }}</h3>

                <div class="story-card__figures">
                    <span class="story-card__raised">
                        <x-ui.money :value="$successStory->raised_amount" :currency="$successStory->currency" :decimals="0" />
                    </span>
                    <span class="story-card__goal">
                        {{ __('home.story_raised_of') }}
                        <x-ui.money :value="$successStory->goal_amount" :currency="$successStory->currency" :decimals="0" />
                        {{ __('home.story_goal') }} ·
                        {{ trans_choice('home.card_donors', $successStory->donations_count, ['count' => $successStory->donations_count]) }}
                    </span>
                </div>

                <x-ui.tally :raised="$successStory->raised_amount"
                            :goal="$successStory->goal_amount"
                            :currency="$successStory->currency"
                            :decimals="0" />

                <p class="story-card__note">{{ __('home.story_text_verified') }}</p>

                <div class="story-card__actions">
                    <a href="{{ route('campaigns.show', $successStory->slug) }}" class="btn btn-primary">
                        {{ __('home.story_cta') }}
                        <x-ui.icon name="arrow-right" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ============================ FAQ ============================ --}}
<section class="section" id="faq" aria-labelledby="faq-title">
    <div class="wrap">
        <div class="section__head" style="justify-content:center;text-align:center;">
            <div style="margin-inline:auto;">
                <p class="kicker" style="justify-content:center;">{{ __('home.faq_kicker') }}</p>
                <h2 id="faq-title" class="h2">{{ __('home.faq_title') }}</h2>
            </div>
        </div>

        <div class="faq">
            <details class="faq-item">
                <summary>
                    {{ __('home.faq1_q') }}
                    <x-ui.icon name="plus" class="faq-item__icon" />
                </summary>
                <div class="faq-item__body"><p>{{ __('home.faq1_a') }}</p></div>
            </details>

            <details class="faq-item">
                <summary>
                    {{ __('home.faq2_q') }}
                    <x-ui.icon name="plus" class="faq-item__icon" />
                </summary>
                <div class="faq-item__body"><p>{{ __('home.faq2_a') }}</p></div>
            </details>

            <details class="faq-item">
                <summary>
                    {{ __('home.faq3_q') }}
                    <x-ui.icon name="plus" class="faq-item__icon" />
                </summary>
                <div class="faq-item__body"><p>{{ __('home.faq3_a') }}</p></div>
            </details>

            <details class="faq-item">
                <summary>
                    {{ __('home.faq4_q') }}
                    <x-ui.icon name="plus" class="faq-item__icon" />
                </summary>
                <div class="faq-item__body">
                    <p>{{ __('home.faq4_a', [
                        'platform' => (float) $feePlatformPct,
                        'processing' => (float) $feeProcessingPct,
                        'flat' => number_format((float) $feeProcessingFlat, 2, '.', ''),
                    ]) }}</p>
                </div>
            </details>

            <details class="faq-item">
                <summary>
                    {{ __('home.faq5_q') }}
                    <x-ui.icon name="plus" class="faq-item__icon" />
                </summary>
                <div class="faq-item__body">
                    <p>{{ __('home.faq5_a', [
                        'withdrawal' => (float) $feeWithdrawalPct,
                        'minimum' => number_format((float) $feeMinWithdrawal, 0, '.', ''),
                    ]) }}</p>
                </div>
            </details>

            <details class="faq-item">
                <summary>
                    {{ __('home.faq6_q') }}
                    <x-ui.icon name="plus" class="faq-item__icon" />
                </summary>
                <div class="faq-item__body"><p>{{ __('home.faq6_a') }}</p></div>
            </details>

            <details class="faq-item">
                <summary>
                    {{ __('home.faq7_q') }}
                    <x-ui.icon name="plus" class="faq-item__icon" />
                </summary>
                <div class="faq-item__body"><p>{{ __('home.faq7_a') }}</p></div>
            </details>
        </div>
    </div>
</section>

{{-- ============================= CTA ============================= --}}
<section class="section">
    <div class="wrap">
        <div class="cta-band">
            <div class="cta-band__inner">
                <h2 class="cta-band__title">{{ __('home.cta_title') }}</h2>
                <p class="cta-band__text">{{ __('home.cta_text') }}</p>
                <div class="cta-band__actions">
                    <a href="{{ route('organizer.campaigns.create') }}" class="btn btn-light btn-lg">
                        {{ __('home.cta_button') }}
                        <x-ui.icon name="arrow-right" />
                    </a>
                    <a href="{{ route('campaigns.index') }}" class="btn btn-outline-light btn-lg">
                        {{ __('home.hero_cta_explore') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
