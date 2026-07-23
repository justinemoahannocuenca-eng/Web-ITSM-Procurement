<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>@yield('title', 'Nexora')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('css/inventory.css') }}" />
        <script>if(localStorage.getItem('sidebarState')==='open'){document.documentElement.style.setProperty('--sidebar-width','250px');document.documentElement.style.setProperty('--sidebar-ml','250px');}</script>
        @stack('head')
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Inter', sans-serif; background: #132b52; color: #fff; min-height: 100vh; }
            ::-webkit-scrollbar { width: 5px; } ::-webkit-scrollbar-track { background: #0b1e3d; } ::-webkit-scrollbar-thumb { background: #1b3a6b; border-radius: 4px; }

            /* Nexora modal system */
            .nexora-modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.6);
                z-index: 20;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .nexora-modal {
                background: #132B52;
                border-radius: 12px;
                padding: 28px;
                width: 100%;
                max-width: 720px;
                margin: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
                color: #fff;
                position: relative;
                overflow: hidden;
            }

            .nexora-modal-logo {
                position: absolute;
                inset: 0;
                background-image: url('/images/Nexora_Logo_Transparent.png');
                background-repeat: no-repeat;
                background-position: center right 20px;
                background-size: 220px auto;
                opacity: 0.08;
                pointer-events: none;
                z-index: 0;
            }

            .nexora-modal > *:not(.nexora-modal-logo) {
                position: relative;
                z-index: 1;
            }

            .nexora-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 24px;
            }

            .nexora-modal-title {
                font-size: 20px;
                font-weight: 700;
                color: #fff;
            }

            .nexora-modal-close {
                background: transparent;
                border: none;
                color: #fff;
                cursor: pointer;
                font-size: 24px;
                line-height: 1;
            }

            .nexora-modal-form {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }

            @media (max-width: 640px) {
                .nexora-modal-form {
                    grid-template-columns: 1fr;
                }
            }

            .nexora-modal-label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #fff;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                margin-bottom: 6px;
            }

            .nexora-modal-input,
            .nexora-modal-select {
                width: 100%;
                padding: 10px 12px;
                border-radius: 12px;
                border: 1px solid #000;
                background: rgba(255, 255, 255, 0.9);
                color: #0f172a;
                font-family: 'Inter', sans-serif;
                outline: none;
            }

            .nexora-modal-input:focus,
            .nexora-modal-select:focus {
                border-color: #1B6FC8;
            }

            .nexora-modal-input:disabled {
                background: #f1f5f9;
                color: #64748b;
                cursor: not-allowed;
            }

            .nexora-modal-error {
                color: #ef4444;
                font-size: 11px;
                margin-top: 4px;
            }

            .nexora-modal-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 24px;
            }

            .nexora-modal-btn-secondary {
                background: transparent;
                color: #fff;
                border: 1px solid #fff;
                border-radius: 8px;
                padding: 10px 18px;
                font-weight: 600;
                cursor: pointer;
            }

            .nexora-modal-btn-primary {
                background: #fff;
                color: #1B6FC8;
                border: 1px solid #000;
                border-radius: 8px;
                padding: 10px 18px;
                font-weight: 600;
                cursor: pointer;
            }

        </style>
        @stack('styles')
    </head>
    <body>
        @include('inventory::partials.header')
        <div id="main">
            <div class="sidebar-overlay" onclick="closeSidebarMobile()"></div>
            @include('inventory::partials.sidebar')
            <div id="page-content">
                @yield('content')
            </div>
        </div>
        @include('inventory::partials.sidebar-scripts')
        @stack('scripts')
        <script>
            // Auto-submit search/filter forms after the user stops typing
            document.querySelectorAll('form input[name="search"]').forEach(function (input) {
                let typingTimer;
                input.addEventListener('input', function () {
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(function () {
                        input.form.submit();
                    }, 700);
                });
            });
        </script>
    </body>
</html>
