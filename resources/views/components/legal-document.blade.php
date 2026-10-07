@props(['title', 'summary', 'icon'])

<x-guest-layout>
    <article class="legal-page">
        <header class="legal-header">
            <a href="{{ route('login') }}" class="legal-back">
                <i data-lucide="arrow-left" aria-hidden="true"></i>
                Back to sign in
            </a>
            <div class="legal-heading">
                <span class="legal-heading__icon"><i data-lucide="{{ $icon }}" aria-hidden="true"></i></span>
                <div>
                    <p>HTC Supply Management System</p>
                    <h1>{{ $title }}</h1>
                    <span>{{ $summary }}</span>
                </div>
            </div>
            <div class="legal-meta">Effective October 7, 2026 · Last updated October 7, 2026</div>
        </header>

        <div class="legal-body">
            {{ $slot }}
        </div>

        <footer class="legal-footer">
            <nav aria-label="Legal documents">
                <a href="{{ route('legal.terms') }}" @if(request()->routeIs('legal.terms')) aria-current="page" @endif>Terms of Service</a>
                <a href="{{ route('legal.privacy') }}" @if(request()->routeIs('legal.privacy')) aria-current="page" @endif>Privacy Policy</a>
            </nav>
            <span>Holy Trinity College Supply Management Office</span>
        </footer>
    </article>

    <style>
        .login-card:has(> .legal-page) { width: min(100%, 920px); padding: 0; }
        .legal-page { color: #25352b; background: #fff; }
        .legal-header { padding: clamp(1.5rem, 4vw, 2.6rem); color: #fff; background: linear-gradient(145deg, #052e16, #166534); }
        .legal-back { display: inline-flex; align-items: center; gap: .45rem; margin-bottom: 1.8rem; color: rgba(255,255,255,.8); font-size: .75rem; font-weight: 700; text-decoration: none; }
        .legal-back:hover { color: #fff; }
        .legal-back svg { width: 15px; height: 15px; }
        .legal-heading { display: flex; align-items: flex-start; gap: 1rem; }
        .legal-heading__icon { width: 48px; height: 48px; flex: 0 0 48px; display: grid; place-items: center; border: 1px solid rgba(255,255,255,.18); border-radius: 14px; background: rgba(255,255,255,.1); }
        .legal-heading__icon svg { width: 23px; height: 23px; }
        .legal-heading p { margin: 0 0 .25rem; color: #bbf7d0; font-size: .65rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .legal-heading h1 { margin: 0; font-size: clamp(1.6rem, 4vw, 2.3rem); font-weight: 800; }
        .legal-heading span { display: block; max-width: 650px; margin-top: .5rem; color: rgba(255,255,255,.76); font-size: .82rem; line-height: 1.55; }
        .legal-meta { margin-top: 1.4rem; color: rgba(255,255,255,.62); font-size: .68rem; }
        .legal-body { padding: clamp(1.5rem, 4vw, 2.75rem); }
        .legal-intro { margin: 0 0 2rem; padding: 1rem 1.1rem; border-left: 3px solid #198754; border-radius: 0 10px 10px 0; color: #405348; background: #f1f8f3; font-size: .82rem; line-height: 1.7; }
        .legal-section + .legal-section { margin-top: 1.8rem; padding-top: 1.8rem; border-top: 1px solid #e7ede9; }
        .legal-section h2 { display: flex; align-items: center; gap: .65rem; margin: 0 0 .75rem; color: #163e27; font-size: 1rem; font-weight: 800; }
        .legal-section h2 span { width: 26px; height: 26px; flex: 0 0 26px; display: grid; place-items: center; border-radius: 8px; color: #17683f; background: #e5f3ea; font-size: .68rem; }
        .legal-section p, .legal-section li { color: #54635a; font-size: .78rem; line-height: 1.75; }
        .legal-section p { margin: 0; }
        .legal-section ul { margin: .65rem 0 0; padding-left: 1.15rem; }
        .legal-section li + li { margin-top: .35rem; }
        .legal-footer { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem clamp(1.5rem, 4vw, 2.75rem); border-top: 1px solid #e7ede9; color: #7b887f; background: #f8faf8; font-size: .68rem; }
        .legal-footer nav { display: flex; flex-wrap: wrap; gap: 1rem; }
        .legal-footer a { color: #287348; font-weight: 700; text-decoration: none; }
        .legal-footer a[aria-current='page'] { text-decoration: underline; text-underline-offset: 3px; }
        @media (max-width: 575.98px) {
            .legal-heading__icon { width: 42px; height: 42px; flex-basis: 42px; }
            .legal-footer { align-items: flex-start; flex-direction: column; }
        }
    </style>
</x-guest-layout>
