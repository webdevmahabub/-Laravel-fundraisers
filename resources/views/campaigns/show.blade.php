@extends('layouts.public')

@section('title', $campaign->title)
@section('description', Str::limit($campaign->short_description, 150))

@php
    $campaignCurrency = strtoupper((string) $campaign->currency);
$symbol = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'BDT' => '৳', 'INR' => '₹', 'PKR' => '₨', 'NPR' => '₨', 'JPY' => '¥'][$campaignCurrency] ?? $campaignCurrency . ' ';
    $daysLeft = $campaign->end_date ? (int) now()->startOfDay()->diffInDays($campaign->end_date->startOfDay(), false) : null;
    $remaining = max(0, (float) $campaign->goal_amount - (float) $campaign->raised_amount);
    $organizer = $campaign->organizerProfile;

    // Gallery: the cover leads, then all stored images. Each path is checked
    // against the storage disk so a missing file never renders as a broken
    // image. If nothing survives, the placeholder below takes over.
    $galleryImages = [];
    if ($campaign->cover_image_path && Storage::disk('public')->exists($campaign->cover_image_path)) {
        $galleryImages[] = [
            'url' => Storage::disk('public')->url($campaign->cover_image_path),
            'caption' => null,
        ];
    }
    foreach ($campaign->images as $image) {
        $url = Storage::disk('public')->url($image->path);
        if (! Storage::disk('public')->exists($image->path)) {
            continue;
        }
        $duplicate = $campaign->cover_image_path && $image->path === $campaign->cover_image_path;
        if (! $duplicate) {
            $galleryImages[] = ['url' => $url, 'caption' => $image->caption];
        }
    }
    $featuredImage = $galleryImages[0] ?? null;
    $galleryThumbs = count($galleryImages) > 1;

    $isLongStory = mb_strlen(trim((string) $campaign->story)) > 900;
@endphp

@push('meta')
    <link rel="canonical" href="{{ route('campaigns.show', $campaign->slug) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $campaign->title }}">
    <meta property="og:description" content="{{ Str::limit($campaign->short_description, 200) }}">
    <meta property="og:url" content="{{ route('campaigns.show', $campaign->slug) }}">
    @if ($featuredImage)
        <meta property="og:image" content="{{ $featuredImage['url'] }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
@endpush

@section('content')

<div class="campaign-detail">

    {{-- ------------------------------------------------------- BREADCRUMB --}}
    <div class="wrap section--tight">
        <nav class="breadcrumb breadcrumb--page" aria-label="Breadcrumb">
            <a href="{{ route('campaigns.index') }}">Home</a>
            <span class="breadcrumb-sep" aria-hidden="true">/</span>
            <a href="{{ route('campaigns.index') }}">Fundraisers</a>
            @if ($campaign->category)
                <span class="breadcrumb-sep" aria-hidden="true">/</span>
                <a href="{{ route('campaigns.index', ['category' => $campaign->category->slug]) }}">{{ $campaign->category->name }}</a>
            @endif
            <span class="breadcrumb-sep" aria-hidden="true">/</span>
            <span aria-current="page">{{ Str::limit($campaign->title, 46) }}</span>
        </nav>
    </div>

    {{-- ------------------------------------------------------- CAMPAIGN HEAD --}}
    <header class="campaign-head wrap">
        <div class="campaign-head__badges">
            @if ($campaign->category)
                <span class="tag">
                    <span class="tag__icon">{{ $campaign->category->icon }}</span>
                    {{ $campaign->category->name }}
                </span>
            @endif

            @if ($campaign->is_verified)
                <span class="verified-badge">
                    <x-ui.icon name="shield-check" :size="13" :stroke-width="2.5" />
                    Verified campaign
                </span>
            @endif

            <x-ui.status :status="$campaign->status" />
        </div>

        <h1 class="h1 campaign-head__title">{{ $campaign->title }}</h1>

        @if ($campaign->short_description)
            <p class="lede mt-3 max-w-[62ch]">{{ $campaign->short_description }}</p>
        @endif

        <div class="campaign-head__meta mt-4">
            @if ($organizer)
                <span class="campaign-head__organizer">
                    <span class="campaign-head__avatar" aria-hidden="true">
                        {{ strtoupper(mb_substr($organizer->full_name, 0, 1)) }}
                    </span>
                    <span>
                        {{ __('home.card_by') }} <strong>{{ $organizer->full_name }}</strong>
                    </span>
                </span>
            @endif

            <span class="campaign-head__meta-item">
                <x-ui.icon name="clock" :size="13" />
                Started {{ $campaign->created_at->format('M j, Y') }}
            </span>

            <span class="campaign-head__meta-item">
                <x-ui.icon name="calendar" :size="13" />
                Last updated {{ $campaign->updated_at->format('M j, Y') }}
            </span>
        </div>
    </header>

    {{-- ----------------------------------------------------- IMAGE GALLERY --}}
    <div class="wrap mt-7">
        <section class="gallery-hero" aria-label="Campaign photos">
            @if ($featuredImage)
                <figure class="gallery-hero__main">
                    <button type="button" class="gallery-hero__zoom" id="gallery-open"
                            aria-label="View image full screen">
                        <img id="gallery-main-img" src="{{ $featuredImage['url'] }}"
                             alt="{{ $featuredImage['caption'] ?? $campaign->title }}"
                             fetchpriority="high" decoding="async">
                        <span class="gallery-hero__hint" aria-hidden="true">
                            <x-ui.icon name="maximize-2" :size="14" />
                        </span>
                    </button>

                    @if ($featuredImage['caption'])
                        <figcaption class="gallery-hero__caption">{{ $featuredImage['caption'] }}</figcaption>
                    @endif
                </figure>

                @if ($galleryThumbs)
                    <div class="gallery-hero__thumbs" id="gallery-thumbs" role="group" aria-label="More photos">
                        @foreach ($galleryImages as $index => $image)
                            <button type="button"
                                    class="gallery-hero__thumb {{ $index === 0 ? 'is-active' : '' }}"
                                    data-index="{{ $index }}"
                                    data-url="{{ $image['url'] }}"
                                    data-caption="{{ $image['caption'] ?? '' }}"
                                    aria-label="Show photo {{ $index + 1 }} of {{ count($galleryImages) }}"
                                    aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                                <img src="{{ $image['url'] }}" alt="" loading="lazy" decoding="async">
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="gallery-hero__placeholder">
                    <x-ui.icon name="image" :size="22" />
                    <p>No photos have been added to this campaign yet.</p>
                </div>
            @endif
        </section>
    </div>

    {{-- Main column + donation rail --}}
    <div class="wrap mt-8 pb-16">
        <div class="campaign-detail-grid">

            {{-- --------------------------------------------------- STORY COLUMN --}}
            <div class="campaign-detail-main">

                {{-- Story --}}
                <section class="detail-section" id="story" aria-labelledby="story-heading">
                    <h2 class="detail-section__title" id="story-heading">About this fundraiser</h2>

                    <div class="story-wrap mt-4 {{ $isLongStory ? 'is-collapsed' : '' }}" id="story-wrap">
                        <div class="prose story" id="story-body">{{ $campaign->story }}</div>
                        <div class="story-fade" aria-hidden="true"></div>
                    </div>

                    @if ($isLongStory)
                        <button type="button" class="detail-section__toggle mt-2" id="story-toggle"
                                aria-expanded="false" aria-controls="story-wrap">
                            <span id="story-toggle-label">Read more</span>
                            <x-ui.icon name="chevron-down" :size="14" />
                        </button>
                    @endif
                </section>

                {{-- Updates --}}
                @if ($campaign->updates->isNotEmpty())
                    <section class="detail-section" id="updates" aria-labelledby="updates-heading">
                        <h2 class="detail-section__title" id="updates-heading">Updates from the organizer</h2>
                        <div class="timeline mt-5">
                            @foreach ($campaign->updates as $update)
                                <article class="timeline__item">
                                    <p class="timeline__date">
                                        <x-ui.icon name="calendar" :size="11" />
                                        {{ $update->created_at->format('M j, Y') }}
                                    </p>
                                    <h3 class="timeline__title mt-1">{{ $update->title }}</h3>
                                    <p class="timeline__body">{{ $update->body }}</p>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Donor list --}}
                <section class="detail-section" id="donors" aria-labelledby="donors-heading">
                    <div class="detail-section__head">
                        <h2 class="detail-section__title" id="donors-heading">Recent supporters</h2>
                        <span class="detail-section__aside">
                            {{ number_format($donorsCount) }} {{ Str::plural('donor', $donorsCount) }}
                        </span>
                    </div>
                    <p class="meta mt-2">
                        Verified gifts only. Donations awaiting manual payment verification are not listed here.
                    </p>

                    @if ($donors->isNotEmpty())
                        <div class="donor-list mt-4">
                            @foreach ($donors as $donation)
                                <div class="donor-row">
                                    <span class="donor-row__avatar" aria-hidden="true">
                                        {{ strtoupper(mb_substr($donation->resolvedDonorName(), 0, 1)) }}
                                    </span>
                                    <span class="donor-row__main">
                                        <span class="donor-row__name">{{ $donation->resolvedDonorName() }}</span>
                                        <span class="donor-row__meta">
                                            {{ $donation->donated_at?->format('M j, Y') }}
                                            @if ($donation->message)
                                                <span class="donor-row__message">
                                                    <x-ui.icon name="message-square" :size="11" />
                                                    {{ Str::limit($donation->message, 90) }}
                                                </span>
                                            @endif
                                        </span>
                                    </span>
                                    <span class="money donor-row__amount">{{ $campaignCurrency }} {{ number_format((float) $donation->gross_amount, 0) }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if ($donors->hasPages())
                            {{ $donors->links('pagination::ledger') }}
                        @endif
                    @else
                        <div class="donor-list donor-list--empty mt-4">
<p>No verified donations yet — this campaign's supporter list will fill in here.</p>
                        </div>
                    @endif
                </section>

                {{-- Organizer --}}
                @if ($organizer)
                    <section class="detail-section" id="organizer" aria-labelledby="organizer-heading">
                        <div class="detail-section__head">
                            <h2 class="detail-section__title" id="organizer-heading">Who is raising the funds</h2>
                        </div>

                        <div class="panel mt-4">
                            <div class="panel__body flex items-start gap-4">
                                <span class="organizer-avatar" aria-hidden="true">
                                    {{ strtoupper(mb_substr($organizer->full_name, 0, 1)) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <p class="font-semibold text-ink m-0">{{ $organizer->full_name }}</p>

                                        @if ($organizer->verified_at)
                                            <span class="verified-badge">
                                                <x-ui.icon name="badge-check" :size="13" :stroke-width="2.5" />
                                                Verified organizer
                                            </span>
                                        @endif
                                    </div>
                                    <p class="meta mt-1">
                                        {{ (int) $organizer->organizer_campaign_count }}
                                        {{ Str::plural('campaign', $organizer->organizer_campaign_count) }} on this platform
                                        @if ($organizer->verified_at)
                                            · Organizing since {{ $organizer->verified_at->format('F Y') }}
                                        @else
                                            · Verification pending
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if ($organizer->bio)
                                <div class="px-4 py-3 border-t border-rule">
                                    <p class="text-sm text-ink-soft leading-relaxed m-0">{{ $organizer->bio }}</p>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif

                {{-- Share --}}
                <section class="detail-section" id="share" aria-labelledby="share-heading">
                    <h2 class="detail-section__title" id="share-heading">Share this campaign</h2>
                    <p class="meta mt-2">Campaigns travel by message. Send this one to someone who would give.</p>

                    <div class="share-list mt-4 sm:grid sm:grid-cols-2 sm:gap-2">
                        <button type="button" class="share-list__btn" id="native-share-btn" hidden>
                            <x-ui.icon name="share-2" :size="15" />
                            Share
                        </button>

                        <a class="share-list__btn" target="_blank" rel="noopener noreferrer"
                           href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('campaigns.show', $campaign->slug)) }}">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            Facebook
                        </a>

                        <a class="share-list__btn" target="_blank" rel="noopener noreferrer"
href="https://wa.me/?text={{ urlencode($campaign->title . ' — ' . route('campaigns.show', $campaign->slug)) }}">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            WhatsApp
                        </a>

                        <a class="share-list__btn" target="_blank" rel="noopener noreferrer"
                           href="https://twitter.com/intent/tweet?text={{ urlencode($campaign->title) }}&amp;url={{ urlencode(route('campaigns.show', $campaign->slug)) }}">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            X (Twitter)
                        </a>

                        <button type="button" class="share-list__btn" id="share-btn">
                            <x-ui.icon name="copy" :size="15" />
                            <span id="share-btn-label">Copy link</span>
                        </button>
                    </div>
                </section>

                {{-- Report --}}
                @auth
                    @if (auth()->id() !== $organizer?->user_id)
                        <section class="detail-section" id="report">
                            <button type="button" class="report-toggle" id="report-toggle"
                                    onclick="document.getElementById('report-form-section').hidden = false; this.hidden = true;">
                                <x-ui.icon name="flag" :size="14" />
                                Something wrong with this campaign? Report it.
                            </button>

                            <div id="report-form-section" class="panel mt-3" hidden>
                                <div class="panel__body">
                                    <form action="{{ route('campaigns.report', $campaign->slug) }}" method="POST">
                                        @csrf
                                        <div class="grid gap-3">
                                            <div class="form-group">
                                                <label for="report_reason" class="form-label">Reason</label>
                                                <select name="reason" id="report_reason" class="form-select" required>
                                                    <option value="">Choose a reason</option>
                                                    <option value="spam">Spam</option>
                                                    <option value="fraud">Fraud or scam</option>
                                                    <option value="inappropriate">Inappropriate content</option>
                                                    <option value="misleading">Misleading information</option>
                                                    <option value="other">Something else</option>
                                                </select>
                                                @error('reason')
                                                    <span class="form-hint error">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label for="report_description" class="form-label">
                                                    Details <span class="form-label-optional">(optional)</span>
                                                </label>
                                                <textarea name="description" id="report_description" class="form-textarea" rows="2"
                                                          placeholder="What did you notice?">{{ old('description') }}</textarea>
                                                @error('description')
                                                    <span class="form-hint error">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="flex gap-2">
                                                <button type="submit" class="btn btn-danger btn-sm">Send report</button>
                                                <button type="button" class="btn btn-ghost btn-sm"
                                                        onclick="document.getElementById('report-form-section').hidden = true; document.getElementById('report-toggle').hidden = false;">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </section>
                    @endif
                @endauth

                {{-- Related campaigns --}}
                @if ($relatedCampaigns->isNotEmpty())
                    <section class="detail-section" id="related" aria-labelledby="related-heading">
                        <h2 class="detail-section__title" id="related-heading">Related campaigns</h2>
                        <div class="related-grid mt-5">
                            @foreach ($relatedCampaigns as $related)
                                <x-ui.campaign-card :campaign="$related" :show-pct="true" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            {{-- ==================================================== DONATION RAIL --}}
            <aside class="campaign-sidebar" id="donation-rail" aria-label="Fundraising progress and donation">

                <div class="donation-box fund-card">
                    {{-- ------------------------------------- PROGRESS CARD --}}
                    <p class="filter__label">Raised so far</p>
                    <p class="figure mt-1"><x-ui.money :value="$campaign->raised_amount" :currency="$campaign->currency" /></p>
                    <p class="donation-goal-text">
                        of <x-ui.money :value="$campaign->goal_amount" :currency="$campaign->currency" /> goal
                        @if ($remaining > 0 && $campaign->goal_amount > 0)
                            · <x-ui.money :value="$remaining" :currency="$campaign->currency" /> still needed
                        @endif
                    </p>

                    <div class="mt-4">
                        <x-ui.tally
                            :raised="$campaign->raised_amount"
                            :goal="$campaign->goal_amount"
                            :currency="$campaign->currency"
                            :showGoal="false"
                        />
                    </div>

                    <div class="donation-stats">
                        <div class="donation-stat">
                            <div class="donation-stat-value">{{ number_format($donorsCount) }}</div>
                            <div class="donation-stat-label">{{ Str::plural('Donor', $donorsCount) }}</div>
                        </div>
                        <div class="donation-stat">
                            <div class="donation-stat-value">{{ (int) $campaign->progressPercentage() }}%</div>
                            <div class="donation-stat-label">Funded</div>
                        </div>
                        <div class="donation-stat">
                            @if ($daysLeft === null)
<div class="donation-stat-value">—</div>
                                <div class="donation-stat-label">No deadline</div>
                            @elseif ($daysLeft > 0)
                                <div class="donation-stat-value">{{ $daysLeft }}</div>
                                <div class="donation-stat-label">{{ Str::plural('Day', $daysLeft) }} left</div>
                            @elseif ($daysLeft === 0)
                                <div class="donation-stat-value">Today</div>
                                <div class="donation-stat-label">Last day</div>
                            @else
                                <div class="donation-stat-value">Ended</div>
                                <div class="donation-stat-label">{{ $campaign->end_date->format('M j, Y') }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- ---------------------------- DONATION ENTRY STATES --}}
                    @if ($donateState === 'open')
                        <button type="button" class="btn btn-lg btn-block" id="donate-toggle-btn">
                            <x-ui.icon name="heart" :size="17" />
                            Donate to this campaign
                        </button>
                    @elseif ($donateState === 'funded')
                        <div class="note note--success">
                            <x-ui.icon name="circle-check" :size="15" />
                            <span>
<strong>Fully funded.</strong> Thank you to everyone who gave — this goal has been reached.
                            </span>
                        </div>
                    @elseif ($donateState === 'expired')
                        <div class="note note--info">
                            <x-ui.icon name="clock" :size="15" />
                            <span>
                                <strong>This campaign ended on {{ $campaign->end_date->format('M j, Y') }}</strong> and is no longer accepting donations.
                            </span>
                        </div>
                    @elseif ($donateState === 'paused')
                        <div class="note note--warning">
                            <x-ui.icon name="info" :size="15" />
                            <span>
                                <strong>Donations are paused.</strong> The organizer has temporarily stopped collecting. Check back soon.
                            </span>
                        </div>
                    @else
                        <div class="note note--info">
                            <x-ui.icon name="circle-check" :size="15" />
                            <span>
                                <strong>This campaign is closed.</strong> Thank you to everyone who gave.
                            </span>
                        </div>
                    @endif

                    {{-- ----------------------------------------------- THE FORM --}}
                    @if ($donateState === 'open')
                        <div id="donate-form-section" class="mt-5 pt-5 border-t border-rule" hidden>
                            <form action="{{ route('campaigns.donate', $campaign->slug) }}" method="POST" id="donate-form">
                                @csrf

                                <div class="grid gap-4">
{{-- Step 1 — amount --}}
                                    <div class="form-group">
                                        <label for="gross_amount" class="form-label">1 · Your amount</label>
                                        <div class="money-field">
                                            <span class="money-field__symbol" aria-hidden="true">{{ $symbol }}</span>
                                            <input type="number" name="gross_amount" id="gross_amount"
                                                   class="form-input @error('gross_amount') is-invalid @enderror"
                                                   value="{{ old('gross_amount', '') }}"
                                                   min="{{ $minimumDonation }}" step="0.01" inputmode="decimal"
                                                   placeholder="{{ number_format($quickAmounts[2] ?? 500) }}.00"
                                                   required>
                                        </div>
                                        @error('gross_amount')
                                            <span class="form-hint error">{{ $message }}</span>
                                        @else
                                            <span class="form-hint">Minimum {{ $campaignCurrency }} {{ number_format((float) $minimumDonation) }}.</span>
                                        @enderror
                                    </div>

                                    <div class="quick-amounts" role="group" aria-label="Quick amounts">
                                        @foreach ($quickAmounts as $preset)
                                            <button type="button" class="quick-amount"
                                                    data-amount="{{ $preset }}"
                                                    onclick="setAmount({{ $preset }})">{{ $symbol }}{{ number_format($preset) }}</button>
                                        @endforeach
                                    </div>

                                    {{-- The receipt: the whole point of the page --}}
                                    <div class="receipt" id="receipt" hidden>
                                        <dl class="m-0">
                                            <div class="receipt__row">
                                                <dt>You give</dt>
<dd class="money text-ink" id="receipt-gross">—</dd>
                                            </div>
                                            <div class="receipt__row">
                                                <dt>Platform fee (<span id="receipt-platform-pct"></span>)</dt>
<dd class="money" id="receipt-platform">—</dd>
                                            </div>
                                            <div class="receipt__row">
                                                <dt>Payment processing</dt>
<dd class="money" id="receipt-processing">—</dd>
                                            </div>
                                            <div class="receipt__row receipt__row--total">
                                                <dt>Organizer receives</dt>
<dd class="money text-ink" id="receipt-net">—</dd>
                                            </div>
                                        </dl>
                                    </div>

{{-- Step 2 — payment method --}}
                                    <div class="form-group">
                                        <label class="form-label">2 · How will you pay?</label>
                                        <div id="payment-methods-list" class="grid gap-2">
<p class="form-hint">Loading payment methods…</p>
                                        </div>
                                        <input type="hidden" name="payment_method" id="payment_method_input" value="{{ old('payment_method') }}">
                                        @error('payment_method')
                                            <span class="form-hint error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Instructions appear once a method is chosen --}}
                                    <div id="payment-instructions-box" hidden>
                                        <div class="pay-instructions">
                                            <p class="pay-instructions__title">
                                                <x-ui.icon name="info" :size="15" />
                                                Send the money
                                            </p>
                                            <div id="payment-instructions-text" class="pay-instructions__body"></div>
                                            <dl id="payment-account-details" class="pay-account-list mt-3"></dl>
                                            <div id="payment-qr-code" class="mt-3" hidden>
                                                <img id="payment-qr-img" src="" alt="Payment QR code"
                                                     class="max-w-[180px] border border-rule rounded-sm">
                                            </div>
                                        </div>
                                    </div>

{{-- Step 3 — transfer confirmation (manual payments) --}}
                                    <div class="form-group" id="manual-transfer-fields">
                                        <label class="form-label">3 · Confirm your transfer</label>
                                        <p class="form-hint">
                                            All payments are verified by our team against the actual transfer, so we
                                            need the reference from your payment confirmation.
                                        </p>

                                        <div class="grid gap-3 mt-2">
                                            <div class="form-group m-0">
                                                <label for="sender_mobile" class="form-label">
Mobile number you sent from <span class="form-label-optional">(e.g. 01712345678)</span>
                                                </label>
                                                <input type="tel" name="sender_mobile" id="sender_mobile"
                                                       class="form-input @error('sender_mobile') is-invalid @enderror"
                                                       value="{{ old('sender_mobile') }}"
                                                       maxlength="30" inputmode="tel"
                                                       placeholder="01XXXXXXXXX">
                                                @error('sender_mobile')
                                                    <span class="form-hint error">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="form-group m-0">
                                                <label for="transaction_id" class="form-label">
                                                    Transaction ID (TrxID)
                                                </label>
                                                <input type="text" name="transaction_id" id="transaction_id"
                                                       class="form-input @error('transaction_id') is-invalid @enderror"
                                                       value="{{ old('transaction_id') }}"
                                                       maxlength="100" autocomplete="off"
                                                       placeholder="The code from your payment confirmation">
                                                @error('transaction_id')
                                                    <span class="form-hint error">{{ $message }}</span>
                                                @enderror
                                                <span class="form-hint">Found in the confirmation SMS or app receipt from your payment provider.</span>
                                            </div>
                                        </div>

                                        <p class="pay-pending-note mt-2">
                                            <x-ui.icon name="clock" :size="13" />
                                            Your donation is recorded as <strong>pending verification</strong>.
It appears publicly once an administrator confirms the transfer —
                                            often within one working day.
                                        </p>
                                    </div>

                                    {{-- Donor information --}}
                                    <div id="donor-fields" class="grid gap-4">
                                        <div class="form-label agree">4 · About you</div>

                                        @auth
                                            <input type="hidden" name="donor_name" value="{{ auth()->user()->name }}">
                                            <input type="hidden" name="donor_email" value="{{ auth()->user()->email }}">
                                            <p class="meta">Giving as <strong>{{ auth()->user()->name }}</strong>.</p>
                                        @else
                                            <div class="form-group">
                                                <label for="donor_name" class="form-label">Your name</label>
                                                <input type="text" name="donor_name" id="donor_name"
                                                       class="form-input @error('donor_name') is-invalid @enderror"
                                                       value="{{ old('donor_name') }}" placeholder="Your name"
                                                       maxlength="255" required>
                                                @error('donor_name')
                                                    <span class="form-hint error">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label for="donor_email" class="form-label">Email</label>
                                                <input type="email" name="donor_email" id="donor_email"
                                                       class="form-input @error('donor_email') is-invalid @enderror"
                                                       value="{{ old('donor_email') }}" placeholder="you@email.com"
                                                       maxlength="255" required>
                                                @error('donor_email')
                                                    <span class="form-hint error">{{ $message }}</span>
                                                @enderror
                                                <span class="form-hint">We use this to count you once and contact you about your donation.</span>
                                            </div>
                                        @endauth

                                        <div class="form-group">
                                            <label for="message" class="form-label">
                                                A note for the organizer <span class="form-label-optional">(optional)</span>
                                            </label>
                                            <textarea name="message" id="message"
                                                      class="form-textarea @error('message') is-invalid @enderror"
                                                      rows="2" maxlength="500"
                                                      placeholder="Words of encouragement">{{ old('message') }}</textarea>
                                            @error('message')
                                                <span class="form-hint error">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <label class="check">
                                        <input type="checkbox" name="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }}>
                                        Hide my name on this campaign (give anonymously)
                                    </label>

                                    <button type="submit" class="btn btn-lg btn-block" id="donate-submit-btn">
                                        Confirm donation
                                    </button>

                                    <button type="button" class="btn btn-ghost btn-sm" id="donate-cancel-btn">Cancel</button>
                                </div>
                            </form>
                        </div>

                        {{-- Payment security note --}}
                        <div class="pay-security mt-4" id="pay-security">
                            <x-ui.icon name="lock" :size="14" />
                            <span>
                                Payments are transferred directly from you and verified against the receipts
                                before they count toward this campaign. No payment details are stored on this site.
                            </span>
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>

{{-- The lightbox --}}
<div id="img-modal" class="lightbox" hidden role="dialog" aria-modal="true" aria-label="Campaign photo">
    <button type="button" class="lightbox__close" aria-label="Close image viewer">
        <x-ui.icon name="x" :size="20" />
    </button>
    <figure class="lightbox__figure">
        <img id="img-modal-src" src="" alt="" class="lightbox__img">
        <figcaption id="img-modal-caption" class="lightbox__caption"></figcaption>
    </figure>
</div>

@push('scripts')
<script>
(function () {
    var fees = {
        platformPct: @json($feeRates['platform_fee_pct']),
        processingPct: @json($feeRates['processing_fee_pct']),
        processingFlat: @json($feeRates['processing_fee_flat']),
    },

    currency = @json($campaignCurrency),
symbols = { USD: '$', EUR: '€', GBP: '£', BDT: '৳', INR: '₹', PKR: '₨', NPR: '₨', JPY: '¥' },
    symbol = symbols[currency] || currency + ' ',

    methods = [],
    selectedMethod = null,

    $ = function (id) { return document.getElementById(id); };

    function money(value) {
        return symbol + Number(value).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    /* ------------------------------------------------------ THE RECEIPT -- */
    function updateReceipt() {
        var input = $('gross_amount');
        var receipt = $('receipt');
        if (!input || !receipt) return;

        var gross = parseFloat(input.value);
        if (!gross || gross <= 0 || isNaN(gross)) {
            receipt.hidden = true;
            return;
        }

        var platform = gross * fees.platformPct / 100;
        var processing = gross * fees.processingPct / 100 + fees.processingFlat;
        var net = Math.max(0, gross - platform - processing);

        $('receipt-gross').textContent = money(gross);
$('receipt-platform').textContent = '−' + money(platform);
$('receipt-processing').textContent = '−' + money(processing);
        $('receipt-net').textContent = money(net);
        $('receipt-platform-pct').textContent = fees.platformPct + '%';
        receipt.hidden = false;

        document.querySelectorAll('.quick-amount').forEach(function (button) {
            button.classList.toggle('selected', Number(button.dataset.amount) === gross);
        });
    }

    function setAmount(value) {
        var input = $('gross_amount');
        if (!input) return;
        input.value = value;
        input.dispatchEvent(new Event('input'));
        input.focus();
    }

    /* ------------------------------------------------- PAYMENT METHODS -- */
    function renderMethods(list) {
        var container = $('payment-methods-list');
        var hiddenInput = $('payment_method_input');
        if (!container) return;
        container.innerHTML = '';

        if (!list.length) {
            container.appendChild(el('p', 'form-hint error',
                'No payment method is available for ' + currency + ' right now. Contact us if you still want to give.'));
            return;
        }

        var only = list.length === 1 ? list[0].slug : null;

        list.forEach(function (method) {
            var label = el('label', 'payment-method-option');

            var radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'payment_method_select';
            radio.value = method.slug;
            radio.checked = hiddenInput.value === method.slug || (!hiddenInput.value && only === method.slug);
            radio.addEventListener('change', function () {
                hiddenInput.value = method.slug;
                selectMethod(method.slug);
            });

var icon = el('span', 'payment-method-option__icon', method.icon || '•');

            var body = el('span', 'min-w-0 flex-1');
            body.appendChild(el('span', 'payment-method-option__name', method.name));
            if (method.description) {
                body.appendChild(el('span', 'payment-method-option__desc block', method.description));
            }

            label.appendChild(radio);
            label.appendChild(icon);
            label.appendChild(body);
            container.appendChild(label);

            if (radio.checked) {
                hiddenInput.value = method.slug;
                selectedMethod = method.slug;
            }
        });

        if (selectedMethod) selectMethod(selectedMethod);
    }

    function selectMethod(slug) {
        var method = methods.filter(function (item) { return item.slug === slug; })[0];
        if (!method) return;

        selectedMethod = slug;

        document.querySelectorAll('.payment-method-option').forEach(function (label) {
            var radio = label.querySelector('input');
            label.classList.toggle('is-selected', radio && radio.checked);
        });

        var box = $('payment-instructions-box');
        var text = $('payment-instructions-text');
        var details = $('payment-account-details');
        var qrBox = $('payment-qr-code');
        var qrImage = $('payment-qr-img');

        var rows = [
            ['Account holder', method.account_holder_name, false],
            ['Account or phone', method.account_number, true],
            ['Bank', method.bank_name, false],
            ['Branch', method.branch_name, false],
            ['Routing / SWIFT', method.routing_number, false],
            ['Contact', method.phone_number, false],
        ].filter(function (row) { return row[1]; });

        if (!method.payment_instructions && !rows.length && !method.qr_code_image_path) {
            box.hidden = true;
            return;
        }

        text.textContent = method.payment_instructions || '';

        details.innerHTML = '';
        rows.forEach(function (row) {
            var line = el('div', 'pay-account');
            line.appendChild(el('dt', null, row[0]));
            var value = el('dd', null, row[1]);
            line.appendChild(value);
            if (row[2]) {
                var copy = el('button', 'link text-xs ml-2', 'copy');
                copy.type = 'button';
                copy.addEventListener('click', function () {
                    navigator.clipboard.writeText(String(row[1])).then(function () {
                        copy.textContent = 'copied';
                        setTimeout(function () { copy.textContent = 'copy'; }, 1800);
                    });
                });
                value.appendChild(copy);
            }
            details.appendChild(line);
        });

        if (method.qr_code_image_path) {
            qrImage.src = '/storage/' + method.qr_code_image_path;
            qrBox.hidden = false;
        } else {
            qrBox.hidden = true;
        }

        box.hidden = false;
    }

    function loadMethods() {
        var container = $('payment-methods-list');
        if (!container) return;
        var type = currency === 'BDT' ? 'BDT' : 'USD';

        fetch('/api/payment-methods/' + type)
            .then(function (response) { return response.json(); })
            .then(function (list) {
                methods.length = 0;
                list.forEach(function (method) { methods.push(method); });
                renderMethods(list);
            })
            .catch(function () {
                container.innerHTML = '';
                container.appendChild(el('p', 'form-hint error', 'Payment methods could not be loaded. Refresh and try again.'));
            });
    }

    /* ------------------------------------------------------- THE FORM -- */
    function openDonateForm() {
        var section = $('donate-form-section');
        var toggle = $('donate-toggle-btn');
        var rail = $('donation-rail');

        if (section) section.hidden = false;
        if (toggle) toggle.hidden = true;
        if (rail) rail.classList.add('is-open');

        if (section) {
            var firstField = section.querySelector('input, select, textarea');
            if (firstField) firstField.focus({ preventScroll: true });
            section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function closeDonateForm() {
        var section = $('donate-form-section');
        var toggle = $('donate-toggle-btn');
        var rail = $('donation-rail');

        if (section) section.hidden = true;
        if (toggle) toggle.hidden = false;
        if (rail) rail.classList.remove('is-open');
    }

    /* --------------------------------------------------------- SHARING -- */
    function copyShareLink() {
        var label = $('share-btn-label');
        var button = $('share-btn');

        navigator.clipboard.writeText(window.location.href).then(function () {
            if (label) label.textContent = 'Link copied';
            if (button) button.classList.add('is-copied');
            setTimeout(function () {
                if (label) label.textContent = 'Copy link';
                if (button) button.classList.remove('is-copied');
            }, 2200);
        });
    }

    function shareNative() {
        if (!navigator.share) return;
        var button = $('native-share-btn');
        if (!button) return;

        button.hidden = false;
        button.addEventListener('click', function () {
            navigator.share({
                title: document.title,
                url: window.location.href,
}).catch(function () { /* user dismissed the sheet — nothing to do */ });
        });
    }

    /* -------------------------------------------------------- GALLERY -- */
    function initGallery() {
        var main = $('gallery-main-img');
        var open = $('gallery-open');
        var thumbs = document.querySelectorAll('#gallery-thumbs .gallery-hero__thumb');

        if (open) {
            open.addEventListener('click', function () {
                openImageModal(main ? main.src : '', main ? main.alt : '');
            });
        }

        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                thumbs.forEach(function (other) {
                    other.classList.remove('is-active');
                    other.setAttribute('aria-pressed', 'false');
                });
                thumb.classList.add('is-active');
                thumb.setAttribute('aria-pressed', 'true');

                if (main) {
                    main.src = thumb.dataset.url;
                    main.alt = thumb.dataset.caption || document.title;
                }

                var figure = main ? main.closest('figure') : null;
                var caption = figure ? figure.querySelector('figcaption') : null;
                if (caption) {
                    caption.textContent = thumb.dataset.caption;
                    caption.hidden = !thumb.dataset.caption;
                }
            });
        });
    }

    function openImageModal(src, caption) {
        var modal = $('img-modal');
        var image = $('img-modal-src');
        var captionEl = $('img-modal-caption');
        if (!modal || !image) return;

        image.src = src;
        image.alt = caption || 'Campaign photo';
        if (captionEl) {
            captionEl.textContent = caption || '';
            captionEl.hidden = !caption;
        }

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        modal.querySelector('.lightbox__close').focus();
    }

    function closeImageModal() {
        var modal = $('img-modal');
        if (!modal) return;

        modal.hidden = true;
        document.body.style.overflow = '';
        $('img-modal-src').src = '';
    }

    function initLightbox() {
        var modal = $('img-modal');
        if (!modal) return;

        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('.lightbox__close')) {
                closeImageModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) closeImageModal();
        });
    }

    /* ------------------------------------------------------- THE STORY -- */
    function initStory() {
        var wrap = $('story-wrap');
        var toggle = $('story-toggle');
        if (!wrap || !toggle) return;

        // Only clamp (and only show the toggle) when the content actually
// overflows — length in characters is a guess, height is the truth.
        wrap.classList.add('is-collapsed');
        var overflows = wrap.scrollHeight > wrap.clientHeight + 2;
        wrap.classList.remove('is-collapsed');

        if (!overflows) {
            toggle.hidden = true;
            wrap.classList.remove('is-collapsed');
            return;
        }

        wrap.classList.add('is-collapsed');
        toggle.addEventListener('click', function () {
            var collapsed = wrap.classList.toggle('is-collapsed');
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            $('story-toggle-label').textContent = collapsed ? 'Read more' : 'Read less';
        });
    }

    /* --------------------------------------------------------- BOOT ----- */
    document.addEventListener('DOMContentLoaded', function () {
        var input = $('gross_amount');
        if (input) {
            input.addEventListener('input', updateReceipt);
            updateReceipt();
        }

        loadMethods();
        initGallery();
        initLightbox();
        initStory();
        shareNative();

        var toggleBtn = $('donate-toggle-btn');
        var cancelBtn = $('donate-cancel-btn');
        if (toggleBtn) toggleBtn.addEventListener('click', openDonateForm);
        if (cancelBtn) cancelBtn.addEventListener('click', closeDonateForm);

        var shareBtn = $('share-btn');
        if (shareBtn) shareBtn.addEventListener('click', copyShareLink);

        @if ($errors->any())
            openDonateForm();
        @endif
    });
})();
</script>
@endpush

@endsection
