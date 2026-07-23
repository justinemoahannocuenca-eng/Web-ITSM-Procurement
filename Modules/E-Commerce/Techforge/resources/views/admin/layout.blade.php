<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Nexora E-commerce' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/nexora-icon.ico') }}">
    <style>
        :root { color: #0b1e3d; background: #f4f7fb; font-family: Inter, Arial, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f7fb; color: #132b52; }
        .itsm-header { min-height: 128px; display: flex; align-items: center; justify-content: space-between; gap: 32px; padding: 16px 48px 16px 16px; background: #0b1e3d; box-shadow: 0 4px 14px rgba(11,30,61,.28); }
        .brand { display: block; height: 96px; flex: 0 0 auto; transition: transform .18s ease; }
        .brand:hover { transform: scale(1.02); }
        .brand img { display: block; height: 100%; max-width: min(520px, 43vw); object-fit: contain; object-position: left center; }
        .header-actions { display: flex; align-items: center; justify-content: flex-end; gap: 42px; }
        .top-nav { display: flex; align-items: center; gap: 30px; white-space: nowrap; }
        .top-nav a { color: rgba(255,255,255,.7); text-decoration: none; font-size: 16px; font-weight: 500; transition: color .18s ease; }
        .top-nav a:hover, .top-nav a.active { color: #60a5fa; }
        .top-nav a.active { font-weight: 700; }
        .user-menu { position: relative; }
        .user-button { display: grid; place-items: center; width: 42px; height: 42px; padding: 0; border: 0; border-radius: 50%; background: transparent; cursor: pointer; }
        .user-button img { width: 36px; height: 36px; object-fit: contain; }
        .user-dropdown { visibility: hidden; position: absolute; z-index: 20; top: 50px; right: 0; width: 220px; overflow: hidden; border-radius: 8px; background: #fff; box-shadow: 0 18px 38px rgba(11,30,61,.28); opacity: 0; transform: translateY(-8px); transition: .16s ease; }
        .user-menu[data-open="true"] .user-dropdown { visibility: visible; opacity: 1; transform: translateY(0); }
        .user-dropdown a, .user-dropdown button { display: block; width: 100%; padding: 14px 18px; border: 0; border-bottom: 1px solid #e2e8f0; background: #fff; color: #0b1e3d; font: 600 14px Inter, Arial, sans-serif; text-align: left; text-decoration: none; cursor: pointer; }
        .user-dropdown a:hover, .user-dropdown button:hover { background: #f1f5f9; color: #1b6fc8; }
        .user-dropdown form { margin: 0; }
        .user-dropdown form button { border-bottom: 0; color: #dc2626; }
        .page { width: min(1280px, calc(100% - 48px)); margin: 0 auto; padding: 42px 0 64px; }
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 28px; }
        h1 { margin: 0; color: #0b1e3d; font-size: clamp(28px, 4vw, 38px); font-weight: 400; }
        .company { margin: 8px 0 0; color: #64748b; font-size: 14px; }
        .card { border: 1px solid #d8e2ee; border-radius: 10px; background: #fff; padding: 22px; box-shadow: 0 4px 14px rgba(19,43,82,.06); }
        .grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
        .metric { margin-top: 8px; color: #0b1e3d; font-size: 30px; font-weight: 800; }
        .muted { color: #64748b; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #e5edf5; text-align: left; }
        th { color: #64748b; font-size: 12px; }
        button, .button { display: inline-block; border: 0; border-radius: 7px; padding: 10px 14px; background: #1d4e89; color: #fff; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button:hover, button:hover { background: #163e6d; }
        .button.alt { background: #e7eff8; color: #132b52; }
        .button.alt:hover { background: #d8e6f5; }
        input, textarea, select { width: 100%; margin-top: 5px; border: 1px solid #bdcadb; border-radius: 7px; padding: 10px; color: #132b52; font: inherit; }
        label { display: block; margin-top: 14px; color: #132b52; font-size: 13px; font-weight: 700; }
        .hint { margin: 6px 0 0; color: #64748b; font-size: 12px; line-height: 1.45; }
        .success { margin-bottom: 16px; border-radius: 7px; padding: 12px; background: #dcfce7; color: #166534; }
        .error { color: #b91c1c; font-size: 13px; }
        .editor-grid { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(280px, .8fr); gap: 20px; align-items: start; }
        .section-card { margin-top: 16px; padding: 18px; border: 1px solid #d8e2ee; border-radius: 9px; background: #fbfdff; }
        .section-card h3 { margin: 0; color: #0b1e3d; font-size: 17px; }
        .section-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .toggle { display: inline-flex; align-items: center; gap: 7px; color: #0b1e3d; font-size: 13px; font-weight: 700; }
        .toggle input { width: auto; margin: 0; accent-color: #1d4e89; }
        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 14px; }
        .order-list { display: grid; gap: 8px; margin-top: 14px; }
        .order-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; border: 1px solid #d8e2ee; border-radius: 7px; padding: 10px 12px; background: #fff; font-size: 13px; font-weight: 700; }
        .order-item button { padding: 4px 8px; border-radius: 5px; font-size: 12px; }
        .publish-note { border-left: 4px solid #1d4e89; background: #edf4fc; padding: 13px; color: #1e3a5f; font-size: 13px; line-height: 1.5; }
        @media(max-width:900px) { .editor-grid { grid-template-columns: 1fr; } }
        @media(max-width:600px) { .field-grid { grid-template-columns: 1fr; } }
        @media (max-width: 900px) { .itsm-header { min-height: auto; padding: 12px 20px; align-items: flex-start; } .brand { height: 60px; } .brand img { max-width: 190px; } .header-actions { gap: 18px; flex-wrap: wrap; } .top-nav { gap: 16px; flex-wrap: wrap; justify-content: flex-end; } .top-nav a { font-size: 14px; } .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .page { width: min(100% - 30px, 1280px); padding-top: 28px; } }
        @media (max-width: 600px) { .itsm-header { gap: 14px; padding: 10px 14px; } .brand { height: 45px; } .brand img { max-width: 130px; } .header-actions { gap: 12px; } .top-nav { gap: 10px; } .top-nav a { font-size: 12px; } .user-button img { width: 30px; height: 30px; } .page-heading { align-items: flex-start; flex-direction: column; } .grid { grid-template-columns: 1fr; } .card { overflow-x: auto; padding: 16px; } }
    </style>
</head>
<body>
    <header class="itsm-header">
        <a class="brand" href="{{ route('ecommerce.admin.dashboard') }}" aria-label="Nexora E-commerce dashboard">
            <img src="{{ asset('images/Banner Transparent.png') }}" alt="Nexora Logo">
        </a>
        <div class="header-actions">
            <nav class="top-nav" aria-label="E-commerce navigation">
                <a class="{{ request()->routeIs('ecommerce.admin.dashboard') ? 'active' : '' }}" href="{{ route('ecommerce.admin.dashboard') }}">Overview</a>
                <a class="{{ request()->routeIs('ecommerce.admin.listings*') ? 'active' : '' }}" href="{{ route('ecommerce.admin.listings') }}">Storefront Listings</a>
                <a class="{{ request()->routeIs('ecommerce.admin.orders') ? 'active' : '' }}" href="{{ route('ecommerce.admin.orders') }}">Storefront Orders</a>
                <a class="{{ request()->routeIs('ecommerce.admin.layout.*') ? 'active' : '' }}" href="{{ route('ecommerce.admin.layout.edit') }}">Edit Storefront</a>
            </nav>
            <div class="user-menu" data-user-menu>
                <button type="button" class="user-button" data-user-menu-button aria-label="Open user menu" aria-expanded="false"><img src="{{ asset('images/icon.png') }}" alt="User"></button>
                <div class="user-dropdown" data-user-menu-dropdown>
                    <a href="{{ route('ecommerce.home', ['store' => auth('ecommerce_admin')->user()?->getCompany()?->ecommerce_slug]) }}" target="_blank" rel="noopener">Open Storefront</a>
                    <form method="post" action="{{ route('ecommerce.admin.logout') }}">@csrf<button type="submit">Log Out</button></form>
                </div>
            </div>
        </div>
    </header>
    <main class="page">
        <div class="page-heading"><div><h1>{{ $heading ?? 'E-commerce Admin' }}</h1><p class="company">{{ auth('ecommerce_admin')->user()?->getCompany()?->company_name }}</p></div></div>
        @if (session('success'))<div class="success">{{ session('success') }}</div>@endif
        @yield('content')
    </main>
    <script>
        document.querySelectorAll('[data-user-menu]').forEach((menu) => {
            const button = menu.querySelector('[data-user-menu-button]');
            button?.addEventListener('click', (event) => { event.stopPropagation(); const open = menu.dataset.open !== 'true'; menu.dataset.open = open ? 'true' : 'false'; button.setAttribute('aria-expanded', String(open)); });
            window.addEventListener('click', () => { menu.dataset.open = 'false'; button?.setAttribute('aria-expanded', 'false'); });
        });
    </script>
</body>
</html>
