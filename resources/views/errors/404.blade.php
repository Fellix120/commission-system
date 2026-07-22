<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>404 - Page Not Found</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/expert-logo2.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --g-blue: #1a73e8;
            --g-blue-dark: #1967d2;
            --g-blue-light: #e8f0fe;

            --g-red: #d93025;
            --g-red-light: #fce8e6;

            --g-yellow: #f9ab00;
            --g-yellow-light: #fef7e0;

            --g-green: #188038;
            --g-green-light: #e6f4ea;

            --g-grey-900: #202124;
            --g-grey-700: #5f6368;
            --g-grey-500: #80868b;
            --g-grey-300: #dadce0;
            --g-grey-100: #f1f3f4;
            --g-grey-50: #f8f9fa;

            --g-white: #ffffff;

            --shadow-1:
                0 1px 2px rgba(60, 64, 67, .30),
                0 1px 3px 1px rgba(60, 64, 67, .15);

            --shadow-2:
                0 1px 3px rgba(60, 64, 67, .30),
                0 4px 8px 3px rgba(60, 64, 67, .15);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            padding: 24px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-family:
                'Google Sans',
                'Roboto',
                -apple-system,
                BlinkMacSystemFont,
                'Segoe UI',
                sans-serif;

            color: var(--g-grey-900);
            background:
                radial-gradient(circle at 12% 15%,
                    rgba(26, 115, 232, .10),
                    transparent 27%),
                radial-gradient(circle at 88% 82%,
                    rgba(249, 171, 0, .09),
                    transparent 25%),
                var(--g-grey-50);

            -webkit-font-smoothing: antialiased;
        }

        .error-page {
            width: 100%;
            max-width: 980px;

            display: grid;
            grid-template-columns: minmax(300px, .9fr) minmax(380px, 1.1fr);
            align-items: center;
            gap: 48px;
        }

        /* Visual card */

        .error-visual {
            min-height: 410px;
            position: relative;
            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;

            background: var(--g-white);
            border: 1px solid var(--g-grey-300);
            border-radius: 20px;
            box-shadow: var(--shadow-1);
        }

        .error-visual::before {
            content: "";
            position: absolute;
            width: 270px;
            height: 270px;
            top: -115px;
            right: -90px;
            border-radius: 50%;
            background: var(--g-blue-light);
        }

        .error-visual::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            left: -72px;
            bottom: -80px;
            border-radius: 50%;
            background: var(--g-yellow-light);
        }

        .error-visual__shape {
            position: absolute;
            width: 80px;
            height: 80px;
            right: 34px;
            bottom: 38px;
            border-radius: 24px;
            transform: rotate(18deg);
            background: var(--g-green-light);
        }

        .error-code-box {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .error-icon {
            width: 92px;
            height: 92px;
            margin: 0 auto 24px;
            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            color: var(--g-blue);
            background: var(--g-blue-light);
            border: 1px solid #d2e3fc;
        }

        .error-icon .material-symbols-outlined {
            font-size: 48px;
            font-variation-settings:
                'FILL' 0,
                'wght' 400,
                'GRAD' 0,
                'opsz' 48;
        }

        .error-code {
            margin: 0;
            font-size: 112px;
            line-height: .9;
            font-weight: 700;
            letter-spacing: -8px;
            color: var(--g-blue);
        }

        .error-code-label {
            margin-top: 20px;
            color: var(--g-grey-500);
            font-size: 13px;
            font-weight: 500;
            letter-spacing: .8px;
            text-transform: uppercase;
        }

        /* Content */

        .error-content {
            padding: 10px 0;
        }

        .error-badge {
            width: fit-content;
            margin-bottom: 20px;
            padding: 8px 13px;

            display: inline-flex;
            align-items: center;
            gap: 8px;

            color: var(--g-red);
            background: var(--g-red-light);
            border: 1px solid #f4c7c3;
            border-radius: 18px;

            font-size: 13px;
            font-weight: 500;
        }

        .error-badge .material-symbols-outlined {
            font-size: 18px;
        }

        .error-title {
            margin: 0 0 16px;
            color: var(--g-grey-900);

            font-size: 42px;
            line-height: 1.16;
            font-weight: 500;
            letter-spacing: -.8px;
        }

        .error-description {
            max-width: 540px;
            margin: 0 0 28px;

            color: var(--g-grey-700);
            font-size: 16px;
            line-height: 1.7;
        }

        /* Buttons */

        .error-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .error-btn {
            min-height: 42px;
            padding: 0 18px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            border: 1px solid transparent;
            border-radius: 21px;

            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;

            cursor: pointer;
            transition:
                background-color .15s ease,
                border-color .15s ease,
                box-shadow .15s ease,
                transform .15s ease;
        }

        .error-btn .material-symbols-outlined {
            font-size: 19px;
        }

        .error-btn--primary {
            color: var(--g-white);
            background: var(--g-blue);
        }

        .error-btn--primary:hover {
            color: var(--g-white);
            background: var(--g-blue-dark);
            box-shadow: var(--shadow-1);
            transform: translateY(-1px);
        }

        .error-btn--outlined {
            color: var(--g-blue);
            background: var(--g-white);
            border-color: var(--g-grey-300);
        }

        .error-btn--outlined:hover {
            color: var(--g-blue-dark);
            background: var(--g-blue-light);
            border-color: var(--g-blue-light);
        }

        /* Help card */

        .error-help {
            max-width: 540px;
            margin-top: 28px;
            padding: 16px 18px;

            display: flex;
            align-items: flex-start;
            gap: 12px;

            color: var(--g-grey-700);
            background: var(--g-white);
            border: 1px solid var(--g-grey-300);
            border-radius: 12px;

            font-size: 13px;
            line-height: 1.6;
        }

        .error-help .material-symbols-outlined {
            margin-top: 1px;
            color: var(--g-grey-500);
            font-size: 20px;
            flex-shrink: 0;
        }

        .error-help strong {
            color: var(--g-grey-900);
            font-weight: 500;
        }

        /* Responsive */

        @media (max-width: 860px) {
            body {
                align-items: flex-start;
            }

            .error-page {
                max-width: 620px;
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .error-visual {
                min-height: 290px;
            }

            .error-code {
                font-size: 88px;
            }

            .error-icon {
                width: 78px;
                height: 78px;
                margin-bottom: 18px;
            }

            .error-icon .material-symbols-outlined {
                font-size: 40px;
            }

            .error-title {
                font-size: 36px;
            }
        }

        @media (max-width: 575px) {
            body {
                padding: 16px;
            }

            .error-page {
                gap: 24px;
            }

            .error-visual {
                min-height: 230px;
                border-radius: 16px;
            }

            .error-code {
                font-size: 70px;
                letter-spacing: -5px;
            }

            .error-icon {
                width: 68px;
                height: 68px;
                margin-bottom: 16px;
            }

            .error-icon .material-symbols-outlined {
                font-size: 34px;
            }

            .error-code-label {
                margin-top: 14px;
            }

            .error-title {
                font-size: 29px;
                letter-spacing: -.4px;
            }

            .error-description {
                font-size: 15px;
            }

            .error-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .error-btn {
                width: 100%;
            }

            .error-help {
                margin-top: 22px;
            }
        }
    </style>
</head>

<body>
    <main class="error-page">

        <section class="error-visual" aria-hidden="true">
            <span class="error-visual__shape"></span>

            <div class="error-code-box">
                <div class="error-icon">
                    <span class="material-symbols-outlined">
                        search_off
                    </span>
                </div>

                <div class="error-code">404</div>

                <div class="error-code-label">
                    Page not found
                </div>
            </div>
        </section>

        <section class="error-content">
            <div class="error-badge">
                <span class="material-symbols-outlined">
                    error
                </span>

                HTTP 404 Error
            </div>

            <h1 class="error-title">
                This page could not be found
            </h1>

            <p class="error-description">
                The page you are trying to open may have been moved, deleted,
                or the address may be incorrect. Check the URL or return to the
                Commission Dashboard.
            </p>

            <div class="error-actions">
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                    class="error-btn error-btn--primary">
                    <span class="material-symbols-outlined">
                        dashboard
                    </span>

                    {{ auth()->check() ? 'Back to Dashboard' : 'Go to Login' }}
                </a>

                <button type="button" class="error-btn error-btn--outlined" onclick="goBackSafely()">
                    <span class="material-symbols-outlined">
                        arrow_back
                    </span>

                    Go Back
                </button>

                <button type="button" class="error-btn error-btn--outlined" onclick="window.location.reload()">
                    <span class="material-symbols-outlined">
                        refresh
                    </span>

                    Reload
                </button>
            </div>

            <div class="error-help">
                <span class="material-symbols-outlined">
                    info
                </span>

                <div>
                    <strong>Error code: HTTP 404</strong><br>

                    If you believe this page should exist, please contact your
                    system administrator.
                </div>
            </div>
        </section>

    </main>

    <script>
        function goBackSafely() {
            if (window.history.length > 1) {
                window.history.back();
                return;
            }

            window.location.href =
                @json(auth()->check() ? route('dashboard') : route('login'));
        }
    </script>
</body>

</html>