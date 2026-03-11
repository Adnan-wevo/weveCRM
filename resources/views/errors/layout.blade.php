<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $code }} – {{ $title }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                min-height: 100vh;
                background-color: #072d3d;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
            }

            .logo {
                display: block;
                margin-bottom: 2.5rem;
            }

            .logo img {
                height: 2.25rem;
                width: auto;
            }

            .card {
                width: 100%;
                max-width: 26rem;
                background-color: rgba(12, 77, 101, 0.6);
                border: 1px solid rgba(26, 122, 158, 0.4);
                border-radius: 1rem;
                padding: 2.5rem 2rem;
                text-align: center;
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            }

            .code {
                font-size: 5rem;
                font-weight: 900;
                letter-spacing: -0.05em;
                color: #1b96c6;
                line-height: 1;
            }

            .title {
                margin-top: 0.75rem;
                font-size: 1.25rem;
                font-weight: 600;
                color: #ffffff;
            }

            .message {
                margin-top: 0.5rem;
                font-size: 0.875rem;
                color: rgba(205, 233, 244, 0.65);
                line-height: 1.5;
            }

            .actions {
                margin-top: 2rem;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            @media (min-width: 480px) {
                .actions { flex-direction: row; justify-content: center; }
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                padding: 0.625rem 1.25rem;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                font-weight: 500;
                text-decoration: none;
                cursor: pointer;
                transition: background-color 0.15s, opacity 0.15s;
            }

            .btn-outline {
                border: 1px solid #1a7a9e;
                color: #cde9f4;
                background: transparent;
            }

            .btn-outline:hover { background-color: rgba(26, 122, 158, 0.25); }

            .btn-primary {
                background-color: #1b96c6;
                color: #ffffff;
                border: 1px solid transparent;
            }

            .btn-primary:hover { background-color: #1a7a9e; }

            .btn svg {
                width: 1rem;
                height: 1rem;
                flex-shrink: 0;
            }

            .footer {
                margin-top: 2rem;
                font-size: 0.75rem;
                color: rgba(205, 233, 244, 0.25);
            }
        </style>
    </head>
    <body>

        <a href="{{ url('/') }}" class="logo">
            <img src="{{ asset('branding/wevetel.png') }}" alt="{{ config('app.name') }}" />
        </a>

        <div class="card">
            <p class="code">{{ $code }}</p>
            <h1 class="title">{{ $title }}</h1>
            <p class="message">{{ $message }}</p>

            <div class="actions">
                <a href="javascript:history.back()" class="btn btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                    </svg>
                    Go Back
                </a>

                <a href="{{ url('/dashboard') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>

        <p class="footer">{{ config('app.name') }}</p>
    </body>
</html>
