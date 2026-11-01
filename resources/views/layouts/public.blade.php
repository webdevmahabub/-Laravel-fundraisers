<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', __('home.meta_description'))">
    @stack('meta')

    @vite(['resources/css/app.css'])
    @fonts
    @stack('styles')
</head>

<body class="fd">

<a class="skip-link" href="#main-content">{{ __('nav.skip_to_content') }}</a>

<nav class="nav" aria-label="Primary">
    <div class="wrap nav__inner">
        <a href="{{ route('campaigns.index') }}" class="brand">
            <span class="brand__mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0-16c-2.5 0-4 1.5-4 3.5S9.5 15 12 15s4 1.5 4 3.5"/>
                </svg>
            </span>
            {{ config('app.name') }}
        </a>

        <div class="nav__links">
            <a href="{{ route('campaigns.index') }}"
               class="nav__link {{ request()->routeIs('campaigns.*') ? 'is-active' : '' }}">{{ __('nav.browse') }}</a>

            <a href="{{ url('/#how-it-works') }}" class="nav__link">{{ __('nav.how_it_works') }}</a>

            <div class="nav__dd" id="cat-dd">
                <button type="button" class="nav__dd-btn" id="cat-dd-btn"
                        aria-expanded="false" aria-haspopup="true">
                    {{ __('nav.categories') }}
                    <x-ui.icon name="chevron-down" />
                </button>
                <div class="nav__dd-menu">
                    @foreach ($navCategories as $navCategory)
                        <a href="{{ route('campaigns.index', ['category' => $navCategory->slug]) }}" class="nav__dd-item">
                            {{ $navCategory->name }}
                            <span class="nav__dd-count">{{ $navCategory->campaigns_count }}</span>
                        </a>
                    @endforeach
                    <a href="{{ route('campaigns.index') }}" class="nav__dd-all">{{ __('nav.all_categories') }}</a>
                </div>
            </div>

            @auth
                <a href="{{ route('organizer.campaigns.index') }}"
                   class="nav__link {{ request()->routeIs('organizer.campaigns.*') ? 'is-active' : '' }}">{{ __('nav.my_campaigns') }}</a>
            @endauth
        </div>

        <div class="nav__actions">
            <form action="{{ route('campaigns.index') }}" method="GET" role="search" class="nav__search">
                <x-ui.icon name="search" />
                <input type="search" name="search" placeholder="{{ __('nav.search_placeholder') }}"
                       aria-label="{{ __('nav.search_label') }}" value="{{ request('search') }}">
            </form>

            <form method="POST" action="{{ route('language.switch') }}" class="nav__lang" aria-label="{{ __('nav.language_label') }}">
                @csrf
                <button type="submit" name="locale" value="en" class="nav__lang-btn {{ app()->getLocale() === 'en' ? 'is-active' : '' }}"
                        aria-pressed="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}">EN</button>
                <button type="submit" name="locale" value="bn" class="nav__lang-btn {{ app()->getLocale() === 'bn' ? 'is-active' : '' }}"
                        aria-pressed="{{ app()->getLocale() === 'bn' ? 'true' : 'false' }}">বাং</button>
            </form>

            <span class="nav__divider" aria-hidden="true"></span>

            @auth
                @if (auth()->user()->hasAnyRole(['admin', 'super-admin']))
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">{{ __('nav.admin') }}</a>
                @endif
                <a href="{{ route('role.home') }}" class="btn btn-secondary btn-sm">{{ __('nav.dashboard') }}</a>
            @else
                <a href="{{ route('tyro-login.login') }}" class="btn btn-ghost btn-sm">{{ __('nav.login') }}</a>
                @if (Route::has('tyro-login.register'))
                    <a href="{{ route('tyro-login.register') }}" class="btn btn-ghost btn-sm">{{ __('nav.register') }}</a>
                @endif
                <a href="{{ route('organizer.campaigns.create') }}" class="btn btn-primary btn-sm">{{ __('nav.start_campaign') }}</a>
            @endauth
        </div>

        <button type="button" class="nav__toggle" id="nav-toggle"
                aria-label="{{ __('nav.menu_open') }}" aria-expanded="false" aria-controls="nav-sheet">
            <svg class="icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
            <svg class="icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
            </svg>
        </button>
    </div>
</nav>

<div class="sheet" id="nav-sheet">
    <div class="wrap">
        <form action="{{ route('campaigns.index') }}" method="GET" role="search" class="sheet__search">
            <x-ui.icon name="search" />
            <input type="search" name="search" placeholder="{{ __('nav.search_placeholder') }}"
                   aria-label="{{ __('nav.search_label') }}" value="{{ request('search') }}">
        </form>

        <a href="{{ route('campaigns.index') }}" class="sheet__link">
            {{ __('nav.browse') }}
            <x-ui.icon name="chevron-right" />
        </a>
        <a href="{{ url('/#how-it-works') }}" class="sheet__link">
            {{ __('nav.how_it_works') }}
            <x-ui.icon name="chevron-right" />
        </a>

        @auth
            <a href="{{ route('organizer.campaigns.index') }}" class="sheet__link">
                {{ __('nav.my_campaigns') }}
                <x-ui.icon name="chevron-right" />
            </a>
            <a href="{{ route('notifications.index') }}" class="sheet__link">
                {{ __('nav.notifications') }}
                <x-ui.icon name="chevron-right" />
            </a>
        @endauth

        <div class="sheet__heading">{{ __('nav.categories') }}</div>
        @foreach ($navCategories as $navCategory)
            <a href="{{ route('campaigns.index', ['category' => $navCategory->slug]) }}" class="sheet__cat">
                {{ $navCategory->name }}
                <span class="sheet__cat-count">{{ $navCategory->campaigns_count }}</span>
            </a>
        @endforeach

        <div class="sheet__lang">
            <form method="POST" action="{{ route('language.switch') }}" class="nav__lang" aria-label="{{ __('nav.language_label') }}">
                @csrf
                <button type="submit" name="locale" value="en" class="nav__lang-btn {{ app()->getLocale() === 'en' ? 'is-active' : '' }}">EN</button>
                <button type="submit" name="locale" value="bn" class="nav__lang-btn {{ app()->getLocale() === 'bn' ? 'is-active' : '' }}">বাং</button>
            </form>
        </div>

        @if (auth()->check())
            <div class="sheet__actions">
                <a href="{{ route('organizer.campaigns.create') }}" class="btn btn-primary btn-block">{{ __('nav.start_campaign') }}</a>
                <a href="{{ route('role.home') }}" class="btn btn-secondary btn-block">{{ __('nav.dashboard') }}</a>
            </div>
        @else
            <div class="sheet__actions">
                <a href="{{ route('organizer.campaigns.create') }}" class="btn btn-primary btn-block">{{ __('nav.start_campaign') }}</a>
                <a href="{{ route('tyro-login.login') }}" class="btn btn-secondary btn-block">{{ __('nav.login') }}</a>
                @if (Route::has('tyro-login.register'))
                    <a href="{{ route('tyro-login.register') }}" class="btn btn-ghost btn-block">{{ __('nav.register') }}</a>
                @endif
            </div>
        @endif
    </div>
</div>

@if (session('success'))
    <div class="wrap section--tight">
        <div class="alert alert-success">
            <x-ui.icon name="circle-check" />
            {{ session('success') }}
        </div>
    </div>
@endif

@if (session('error'))
    <div class="wrap section--tight">
        <div class="alert alert-error">
            <x-ui.icon name="info" />
            {{ session('error') }}
        </div>
    </div>
@endif

<main id="main-content">@yield('content')</main>

<footer class="site-footer">
    <div class="wrap">
        <div class="footer-grid">
            <div>
                <div class="footer-brand">
                    <span class="mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m0-16c-2.5 0-4 1.5-4 3.5S9.5 15 12 15s4 1.5 4 3.5"/>
                        </svg>
                    </span>
                    {{ config('app.name') }}
                </div>
                <p class="footer-desc">{{ __('footer.tagline') }}</p>
                <div class="footer-social">
                    {{-- Social profiles are not configured in this project yet — placeholders pending real URLs. --}}
                    <a href="#" class="social-link" aria-label="{{ __('footer.facebook') }}">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" class="social-link" aria-label="{{ __('footer.x') }}">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="social-link" aria-label="{{ __('footer.youtube') }}">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                    <a href="#" class="social-link" aria-label="{{ __('footer.instagram') }}">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zm0 10.162a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
                    </a>
                </div>
            </div>

            <div>
                <div class="footer-col-title">{{ __('footer.platform') }}</div>
                <div class="footer-links">
                    <a href="{{ route('campaigns.index') }}" class="footer-link">{{ __('footer.browse') }}</a>
                    <a href="{{ route('organizer.campaigns.create') }}" class="footer-link">{{ __('nav.start_campaign') }}</a>
                    <a href="{{ url('/#how-it-works') }}" class="footer-link">{{ __('footer.how_it_works') }}</a>
                    <a href="{{ url('/#trust') }}" class="footer-link">{{ __('footer.trust_safety') }}</a>
                </div>
            </div>

            <div>
                <div class="footer-col-title">{{ __('footer.categories') }}</div>
                <div class="footer-links">
                    @foreach ($navCategories->take(6) as $navCategory)
                        <a href="{{ route('campaigns.index', ['category' => $navCategory->slug]) }}" class="footer-link">{{ $navCategory->name }}</a>
                    @endforeach
                    <a href="{{ route('campaigns.index') }}" class="footer-link">{{ __('nav.all_categories') }}</a>
                </div>
            </div>

            <div>
                <div class="footer-col-title">{{ __('footer.help') }}</div>
                <div class="footer-links">
                    <a href="{{ url('/#faq') }}" class="footer-link">{{ __('footer.help_faq') }}</a>
                    <a href="{{ url('/#how-it-works') }}" class="footer-link">{{ __('footer.help_how_donations') }}</a>
                    <a href="{{ url('/#trust') }}" class="footer-link">{{ __('footer.trust_safety') }}</a>
                </div>
            </div>

            <div>
                <div class="footer-col-title">{{ __('footer.legal') }}</div>
                <div class="footer-links">
                    {{-- Terms/Privacy pages do not exist in the app yet; these links are placeholders. --}}
                    <a href="#" class="footer-link">{{ __('footer.terms') }}</a>
                    <a href="#" class="footer-link">{{ __('footer.privacy') }}</a>
                </div>
            </div>
        </div>

        <div class="footer-divider footer-bottom">
            <span>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('footer.rights') }}</span>
            <span class="footer-location">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                {{ __('footer.location') }}
            </span>
            <form method="POST" action="{{ route('language.switch') }}" class="footer-lang" aria-label="{{ __('footer.language_label') }}">
                @csrf
                <button type="submit" name="locale" value="en" class="footer-lang-btn {{ app()->getLocale() === 'en' ? 'is-active' : '' }}">EN</button>
                <button type="submit" name="locale" value="bn" class="footer-lang-btn {{ app()->getLocale() === 'bn' ? 'is-active' : '' }}">বাং</button>
            </form>
        </div>
    </div>
</footer>

@vite(['resources/js/app.js'])
@stack('scripts')

<script>
(function () {
    var toggle = document.getElementById('nav-toggle');
    var sheet = document.getElementById('nav-sheet');
    if (!toggle || !sheet) return;

    function setOpen(open) {
        sheet.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? '{{ __('nav.menu_close') }}' : '{{ __('nav.menu_open') }}');
        document.body.style.overflow = open ? 'hidden' : '';
    }

    toggle.addEventListener('click', function () {
        setOpen(!sheet.classList.contains('is-open'));
    });

    sheet.querySelectorAll('a, form').forEach(function (el) {
        el.addEventListener('click', function () { setOpen(false); });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sheet.classList.contains('is-open')) {
            setOpen(false);
            toggle.focus();
        }
    });

    // Categories dropdown (desktop): hover opens it, click keeps it open for
    // touch, Escape and outside clicks close it.
    var dd = document.getElementById('cat-dd');
    var ddBtn = document.getElementById('cat-dd-btn');
    if (dd && ddBtn) {
        function setDd(open) {
            dd.classList.toggle('is-open', open);
            ddBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        ddBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            setDd(!dd.classList.contains('is-open'));
        });

        document.addEventListener('click', function (event) {
            if (!dd.contains(event.target)) setDd(false);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && dd.classList.contains('is-open')) {
                setDd(false);
                ddBtn.focus();
            }
        });
    }
})();
</script>
</body>
</html>
