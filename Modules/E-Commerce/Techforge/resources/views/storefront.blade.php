@php
    $sections = collect($layout['sections'] ?? [])->keyBy('id');
    $enabledSections = collect($layout['sections'] ?? [])->filter(fn (array $section): bool => (bool) ($section['enabled'] ?? false));
    $hero = $sections->get('hero', []);
    $listingsSection = $sections->get('featured_listings', []);
    $promo = $sections->get('promo', []);
    $benefits = $sections->get('benefits', []);
    $store = $company->ecommerce_slug;
    $storefrontUrl = route('ecommerce.home', ['store' => $store]);
    $logoUrl = !empty($layout['logo_path']) ? asset('storage/'.$layout['logo_path']) : ($company->logoUrl() ?: asset('ecommerce/Nexora_Logo.png'));
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $layout['brand_name'] }} | {{ $layout['tagline'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '{{ $layout['primary_color'] }}', accent: '{{ $layout['accent_color'] }}' }, fontFamily: { sans: ['Inter', 'Arial', 'sans-serif'] } } } };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #07101f; color: #f8fafc; }
        .glow { background: radial-gradient(circle at 75% 20%, {{ $layout['primary_color'] }}44 0, transparent 28%), radial-gradient(circle at 15% 10%, {{ $layout['accent_color'] }}22 0, transparent 25%); }
        .grid-overlay { background-image: linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px); background-size: 38px 38px; }
    </style>
</head>
<body class="min-h-screen font-sans">
    @if ($preview)
        <div class="sticky top-0 z-[100] bg-amber-400 px-5 py-3 text-center text-sm font-bold text-slate-950">Preview mode — this draft is not public until you publish it from E-commerce Admin.</div>
    @endif

    <header class="border-b border-white/10 bg-[#081426]/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-5 py-4">
            <a href="{{ $storefrontUrl }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ $logoUrl }}" alt="{{ $layout['brand_name'] }} logo" class="h-11 w-11 rounded-xl object-contain {{ $company->logoUrl() || !empty($layout['logo_path']) ? 'bg-white p-1' : '' }}">
                <span class="truncate text-lg font-extrabold tracking-wide text-white">{{ $layout['brand_name'] }}</span>
            </a>
            <nav class="hidden items-center gap-7 text-sm font-semibold text-slate-300 md:flex">
                <a class="transition hover:text-white" href="{{ $storefrontUrl }}#products">Products</a>
                <a class="transition hover:text-white" href="{{ $storefrontUrl }}#about">About</a>
                <a class="transition hover:text-white" href="{{ route('ecommerce.cart', ['store' => $store]) }}">Cart</a>
                <a class="rounded-lg bg-primary px-4 py-2 text-slate-950 transition hover:brightness-110" href="{{ route('ecommerce.login', ['store' => $store]) }}">Sign in</a>
            </nav>
        </div>
    </header>

    @foreach ($enabledSections as $section)
        @if ($section['id'] === 'hero')
            <section class="glow grid-overlay overflow-hidden border-b border-white/10">
                <div class="mx-auto grid min-h-[520px] max-w-7xl items-center gap-10 px-5 py-20 lg:grid-cols-2">
                    <div class="relative z-10">
                        <p class="mb-4 text-sm font-bold uppercase tracking-[.22em] text-primary">{{ $layout['tagline'] }}</p>
                        <h1 class="max-w-3xl text-5xl font-extrabold leading-tight text-white sm:text-6xl">{{ $section['title'] }} <span class="text-primary">{{ $section['highlight'] }}</span></h1>
                        @if (!empty($section['body']))<p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">{{ $section['body'] }}</p>@endif
                        @if (!empty($section['button_label']))<a href="{{ $section['button_url'] ?: '#products' }}" class="mt-9 inline-flex rounded-lg bg-primary px-6 py-3 font-extrabold text-slate-950 transition hover:brightness-110">{{ $section['button_label'] }} <span class="ml-2">→</span></a>@endif
                    </div>
                    <div class="relative min-h-[290px] overflow-hidden rounded-3xl border border-white/10 bg-[#0b1b33] shadow-2xl">
                        @if (!empty($section['image_path']))
                            <img src="{{ asset('storage/'.$section['image_path']) }}" alt="{{ $section['title'] }}" class="absolute inset-0 h-full w-full object-cover opacity-80">
                        @else
                            <div class="absolute inset-0 bg-gradient-to-br from-primary/30 via-transparent to-accent/20"></div>
                            <div class="absolute inset-7 rounded-2xl border border-white/15"></div>
                            <div class="absolute inset-x-10 bottom-10 text-center text-sm font-bold uppercase tracking-[.28em] text-white/70">{{ $layout['brand_name'] }}</div>
                        @endif
                    </div>
                </div>
            </section>
        @elseif ($section['id'] === 'featured_listings')
            <section id="products" class="mx-auto max-w-7xl px-5 py-20">
                <div class="mb-10 flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-bold uppercase tracking-[.2em] text-primary">Store catalog</p><h2 class="mt-2 text-3xl font-extrabold text-white">{{ $section['title'] }}</h2>@if (!empty($section['body']))<p class="mt-3 text-slate-400">{{ $section['body'] }}</p>@endif</div><span class="text-sm text-slate-400">Live stock shown per item</span></div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($storefrontListings as $listing)
                        <article class="overflow-hidden rounded-2xl border border-white/10 bg-[#0b1b33] transition hover:-translate-y-1 hover:border-primary/60">
                            <div class="flex h-48 items-center justify-center bg-slate-950/60">@if($listing->image_url)<img src="{{ asset('storage/'.$listing->image_url) }}" alt="{{ $listing->name }}" class="h-full w-full object-cover">@else<span class="text-sm font-bold uppercase tracking-[.2em] text-slate-500">{{ $layout['brand_name'] }}</span>@endif</div>
                            <div class="p-5"><div class="flex items-start justify-between gap-3"><h3 class="font-bold text-white">{{ $listing->name }}</h3><span class="whitespace-nowrap text-lg font-extrabold text-primary">₱{{ number_format((float) $listing->price, 2) }}</span></div><p class="mt-2 min-h-10 text-sm leading-5 text-slate-400">{{ \Illuminate\Support\Str::limit($listing->description, 90) }}</p><div class="mt-5 flex items-center justify-between gap-3"><span class="text-xs font-semibold {{ $listing->available_quantity > 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $listing->available_quantity > 0 ? $listing->available_quantity.' available' : 'Out of stock' }}</span><a class="rounded-lg border border-white/15 px-3 py-2 text-sm font-bold text-white hover:border-primary hover:text-primary" href="{{ route('ecommerce.listings.show', ['store' => $store, 'listing' => $listing]) }}">View product</a></div></div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-white/15 p-10 text-center text-slate-400">This store has no published products yet.</div>
                    @endforelse
                </div>
            </section>
        @elseif ($section['id'] === 'promo')
            <section id="about" class="border-y border-white/10 bg-[#0b1b33]">
                <div class="mx-auto max-w-7xl px-5 py-16 text-center"><p class="text-sm font-bold uppercase tracking-[.2em] text-primary">{{ $layout['brand_name'] }}</p><h2 class="mx-auto mt-3 max-w-3xl text-3xl font-extrabold text-white">{{ $section['title'] }}</h2><p class="mx-auto mt-5 max-w-2xl leading-7 text-slate-300">{{ $section['body'] }}</p>@if(!empty($section['button_label']))<a class="mt-8 inline-block rounded-lg bg-primary px-6 py-3 font-extrabold text-slate-950 transition hover:brightness-110" href="{{ $section['button_url'] ?: '#products' }}">{{ $section['button_label'] }}</a>@endif</div>
            </section>
        @elseif ($section['id'] === 'benefits')
            <section class="mx-auto max-w-7xl px-5 py-20"><h2 class="text-center text-3xl font-extrabold text-white">{{ $section['title'] }}</h2><div class="mt-10 grid gap-5 md:grid-cols-3">@foreach (['benefit_one', 'benefit_two', 'benefit_three'] as $benefit)<div class="rounded-2xl border border-white/10 bg-[#0b1b33] p-7 text-center"><div class="mx-auto mb-4 grid h-10 w-10 place-items-center rounded-full bg-primary font-black text-slate-950">✓</div><p class="font-bold text-white">{{ $section[$benefit] }}</p></div>@endforeach</div></section>
        @endif
    @endforeach

    <footer class="border-t border-white/10 bg-[#06101e]"><div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-8 text-sm text-slate-400"><span>© {{ date('Y') }} {{ $layout['brand_name'] }}. Powered by Nexora ERP.</span><a href="{{ route('ecommerce.login', ['store' => $store]) }}" class="hover:text-white">Customer sign in</a></div></footer>
</body>
</html>
