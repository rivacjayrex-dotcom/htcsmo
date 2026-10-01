<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14532d">
    @include('layouts.partials.browser-title')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --auth-green-950: #052e16;
            --auth-green-900: #14532d;
            --auth-green-800: #166534;
            --auth-green-700: #15803d;
            --auth-text: #17221b;
            --auth-muted: #647067;
            --auth-border: #dce4de;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { min-height: 100%; }
        [hidden] { display: none !important; }

        body {
            min-height: 100dvh;
            margin: 0;
            padding: clamp(1rem, 3vw, 2.5rem);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            color: var(--auth-text);
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background:
                radial-gradient(circle at 8% 12%, rgba(34, 197, 94, .15), transparent 26rem),
                radial-gradient(circle at 92% 88%, rgba(20, 83, 45, .12), transparent 24rem),
                #f4f7f4;
        }

        .login-card {
            width: min(100%, 34rem);
            padding: clamp(1.5rem, 5vw, 2.5rem);
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(20, 83, 45, .08);
            border-radius: 1.5rem;
            box-shadow: 0 24px 70px rgba(15, 48, 27, .14);
        }

        .login-card:has(> .auth-page) {
            width: min(100%, 900px);
            min-height: min(540px, calc(100dvh - clamp(2rem, 6vw, 5rem)));
            padding: 0;
        }

        .login-card > .auth-page, .auth-page > .row {
            width: 100%;
            min-height: inherit;
        }

        .auth-brand-panel {
            position: relative;
            isolation: isolate;
            min-height: 100%;
            padding: clamp(1.75rem, 3.5vw, 2.75rem);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(145deg, var(--auth-green-950), var(--auth-green-800));
        }

        .auth-brand-panel::before, .auth-brand-panel::after {
            content: '';
            position: absolute;
            z-index: -1;
            border-radius: 999px;
            background: rgba(255, 255, 255, .06);
        }

        .auth-brand-panel::before {
            width: 18rem;
            height: 18rem;
            top: -8rem;
            right: -9rem;
        }

        .auth-brand-panel::after {
            width: 12rem;
            height: 12rem;
            bottom: -5rem;
            left: -5rem;
        }

        .auth-brand-logo {
            width: clamp(3.75rem, 6vw, 4.5rem);
            height: clamp(3.75rem, 6vw, 4.5rem);
            padding: .45rem;
            object-fit: contain;
            background: rgba(255, 255, 255, .96);
            border-radius: 1.2rem;
            box-shadow: 0 12px 30px rgba(0, 0, 0, .18);
        }

        .auth-brand-eyebrow {
            margin: 1.5rem 0 .65rem;
            color: #bbf7d0;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .15em;
            text-transform: uppercase;
        }

        .auth-brand-title {
            max-width: 20rem;
            margin: 0;
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -.035em;
        }

        .auth-brand-description {
            max-width: 22rem;
            margin: 1rem 0 0;
            color: rgba(255, 255, 255, .72);
            font-size: .9rem;
            line-height: 1.65;
        }

        .auth-brand-footer {
            margin-top: 2rem;
            color: rgba(255, 255, 255, .55);
            font-size: .72rem;
        }

        .auth-form-panel {
            min-height: 100%;
            padding: clamp(1.75rem, 3.5vw, 3rem);
            display: flex;
            align-items: center;
            background: #fff;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 35rem;
            margin-inline: auto;
        }

        .auth-register-page .row.g-3 {
            --bs-gutter-y: .75rem;
        }

        .auth-form-heading { margin-bottom: 1.4rem; }
        .auth-form-heading h1 {
            margin: 0 0 .45rem;
            color: var(--auth-text);
            font-size: clamp(1.5rem, 2.5vw, 1.8rem);
            font-weight: 800;
            letter-spacing: -.035em;
        }

        .auth-form-heading p {
            margin: 0;
            color: var(--auth-muted);
            font-size: .9rem;
            line-height: 1.55;
        }

        .auth-form-label {
            margin-bottom: .45rem;
            color: #35433a;
            font-size: .78rem;
            font-weight: 700;
        }

        .auth-input .input-group-text, .auth-input .form-control, .auth-input .form-select {
            min-height: 2.75rem;
            border-color: var(--auth-border);
            background-color: #fff;
            font-size: .875rem;
        }

        .auth-input .input-group-text {
            padding-inline: .9rem;
            color: #6d786f;
            background: #f8faf8;
        }

        .auth-input .form-control, .auth-input .form-select { padding-inline: .9rem; }
        .auth-input .form-control:focus, .auth-input .form-select:focus {
            z-index: 3;
            border-color: #22a447;
            box-shadow: 0 0 0 .22rem rgba(34, 164, 71, .12);
        }

        .auth-input .form-control::placeholder { color: #a0aaa3; }
        .password-toggle {
            min-width: 2.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #69756c;
            background: #f8faf8;
            border-color: var(--auth-border);
        }

        .password-toggle:hover, .password-toggle:focus-visible {
            color: var(--auth-green-800);
            background: #eef8f0;
            border-color: #8dcc9c;
        }

        .auth-primary-button {
            min-height: 2.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            color: #fff;
            font-size: .86rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--auth-green-800), var(--auth-green-700));
            border: 0;
            border-radius: .65rem;
            box-shadow: 0 10px 22px rgba(21, 128, 61, .2);
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .auth-primary-button:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 13px 26px rgba(21, 128, 61, .26);
        }

        .auth-link {
            color: var(--auth-green-700);
            font-weight: 700;
            text-decoration: none;
        }

        .auth-link:hover { color: var(--auth-green-900); text-decoration: underline; }
        .auth-help { color: #7b857e; font-size: .7rem; line-height: 1.45; }
        .invalid-feedback { font-size: .72rem; }

        .auth-note {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            padding: .8rem .9rem;
            color: #3e5d47;
            font-size: .73rem;
            line-height: 1.45;
            background: #f0f9f2;
            border: 1px solid #d5efdb;
            border-radius: .75rem;
        }

        .auth-note svg { flex: 0 0 auto; margin-top: .08rem; }

        @media (max-width: 767.98px) {
            body { padding: 0; align-items: stretch; background: #fff; }
            .login-card:has(> .auth-page) {
                min-height: 100dvh;
                padding: 0;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .auth-page > .row { min-height: 100dvh; }
            .auth-brand-panel { min-height: auto; padding: 1.25rem; display: block; }
            .auth-brand-main {
                display: grid;
                grid-template-columns: auto 1fr;
                column-gap: 1rem;
                align-items: center;
            }

            .auth-brand-logo {
                grid-row: 1 / span 3;
                width: 3.75rem;
                height: 3.75rem;
                border-radius: .9rem;
            }

            .auth-brand-eyebrow { margin: 0 0 .25rem; font-size: .62rem; }
            .auth-brand-title { font-size: 1.25rem; line-height: 1.2; }
            .auth-brand-description, .auth-brand-footer { display: none; }
            .auth-form-panel {
                min-height: 0;
                padding: 1.75rem 1.25rem 2.25rem;
                align-items: flex-start;
            }

            .auth-form-heading { margin-bottom: 1.4rem; }
        }

        @media (max-width: 359.98px) {
            .auth-brand-panel, .auth-form-panel { padding-left: 1rem; padding-right: 1rem; }
            .auth-brand-logo { width: 3.25rem; height: 3.25rem; }
            .auth-brand-title { font-size: 1.08rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; }
        }
    </style>
</head>
<body>
    <main class="login-card">
        {{ $slot }}
    </main>

    @include('layouts.partials.runtime-guard')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>
