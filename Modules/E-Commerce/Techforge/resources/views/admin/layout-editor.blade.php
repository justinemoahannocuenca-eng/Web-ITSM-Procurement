@extends('ecommerce::admin.layout', ['title' => 'Edit Storefront', 'heading' => 'Edit Storefront'])

@php
    $sections = collect($layout['sections'] ?? [])->keyBy('id');
    $hero = $sections->get('hero', []);
    $listings = $sections->get('featured_listings', []);
    $promo = $sections->get('promo', []);
    $benefits = $sections->get('benefits', []);
    $order = collect($layout['sections'] ?? [])->pluck('id')->implode(',');
@endphp

@section('content')
<div class="editor-grid">
    <form class="card" method="post" enctype="multipart/form-data" action="{{ route('ecommerce.admin.layout.save') }}">
        @csrf @method('put')
        @if ($errors->any())<p class="error">{{ $errors->first() }}</p>@endif
        <div class="section-top"><div><h2 style="margin:0">Storefront content</h2><p class="muted">Configure safe building blocks. Product data and inventory remain controlled by the ERP.</p></div><a class="button alt" target="_blank" rel="noopener" href="{{ route('ecommerce.admin.layout.preview') }}">Preview draft</a></div>

        <div class="field-grid">
            <label>Store name<input name="brand_name" value="{{ old('brand_name', $layout['brand_name']) }}" required></label>
            <label>Tagline<input name="tagline" value="{{ old('tagline', $layout['tagline']) }}"></label>
            <label>Primary color<input type="color" name="primary_color" value="{{ old('primary_color', $layout['primary_color']) }}" required></label>
            <label>Accent color<input type="color" name="accent_color" value="{{ old('accent_color', $layout['accent_color']) }}" required></label>
            <label>Store logo<input type="file" name="logo" accept="image/*"><span class="hint">Optional. This overrides the logo supplied during client setup.</span></label>
        </div>

        <input id="section-order" type="hidden" name="section_order" value="{{ old('section_order', $order) }}">

        <section class="section-card" data-section="hero">
            <div class="section-top"><h3>Hero</h3><label class="toggle"><input type="checkbox" name="hero_enabled" @checked(old('hero_enabled', $hero['enabled'] ?? false))> Show section</label></div>
            <div class="field-grid"><label>Headline<input name="hero_title" value="{{ old('hero_title', $hero['title'] ?? '') }}"></label><label>Highlighted text<input name="hero_highlight" value="{{ old('hero_highlight', $hero['highlight'] ?? '') }}"></label></div>
            <label>Description<textarea name="hero_body">{{ old('hero_body', $hero['body'] ?? '') }}</textarea></label>
            <div class="field-grid"><label>Button label<input name="hero_button_label" value="{{ old('hero_button_label', $hero['button_label'] ?? '') }}"></label><label>Button link<input name="hero_button_url" value="{{ old('hero_button_url', $hero['button_url'] ?? '#products') }}"><span class="hint">Use a relative path, #products, or an https URL.</span></label></div>
            <label>Hero image<input type="file" name="hero_image" accept="image/*"><span class="hint">Optional. A branded visual is used when no image is uploaded.</span></label>
        </section>

        <section class="section-card" data-section="featured_listings">
            <div class="section-top"><h3>Featured products</h3><label class="toggle"><input type="checkbox" name="featured_listings_enabled" @checked(old('featured_listings_enabled', $listings['enabled'] ?? false))> Show section</label></div>
            <div class="field-grid"><label>Section title<input name="listings_title" value="{{ old('listings_title', $listings['title'] ?? '') }}"></label><label>Supporting text<input name="listings_body" value="{{ old('listings_body', $listings['body'] ?? '') }}"></label></div>
            <p class="hint">Only active client listings appear here. E-commerce cannot alter inventory quantities or Manufacturing BOMs.</p>
        </section>

        <section class="section-card" data-section="promo">
            <div class="section-top"><h3>Promotional banner</h3><label class="toggle"><input type="checkbox" name="promo_enabled" @checked(old('promo_enabled', $promo['enabled'] ?? false))> Show section</label></div>
            <label>Headline<input name="promo_title" value="{{ old('promo_title', $promo['title'] ?? '') }}"></label><label>Message<textarea name="promo_body">{{ old('promo_body', $promo['body'] ?? '') }}</textarea></label>
            <div class="field-grid"><label>Button label<input name="promo_button_label" value="{{ old('promo_button_label', $promo['button_label'] ?? '') }}"></label><label>Button link<input name="promo_button_url" value="{{ old('promo_button_url', $promo['button_url'] ?? '#products') }}"></label></div>
        </section>

        <section class="section-card" data-section="benefits">
            <div class="section-top"><h3>Benefits</h3><label class="toggle"><input type="checkbox" name="benefits_enabled" @checked(old('benefits_enabled', $benefits['enabled'] ?? false))> Show section</label></div>
            <label>Section title<input name="benefits_title" value="{{ old('benefits_title', $benefits['title'] ?? '') }}"></label>
            <div class="field-grid"><label>Benefit one<input name="benefit_one" value="{{ old('benefit_one', $benefits['benefit_one'] ?? '') }}"></label><label>Benefit two<input name="benefit_two" value="{{ old('benefit_two', $benefits['benefit_two'] ?? '') }}"></label><label>Benefit three<input name="benefit_three" value="{{ old('benefit_three', $benefits['benefit_three'] ?? '') }}"></label></div>
        </section>
        <p style="margin:20px 0 0"><button type="submit">Save draft</button></p>
    </form>

    <aside class="card">
        <h2 style="margin-top:0">Layout controls</h2>
        <p class="muted">Turn a section on to add it to your store; turn it off to remove it. Use the arrows to control the live order.</p>
        <div id="section-order-list" class="order-list">
            @foreach (collect(explode(',', $order))->filter() as $id)
                @php($label = ['hero' => 'Hero', 'featured_listings' => 'Featured products', 'promo' => 'Promotional banner', 'benefits' => 'Benefits'][$id] ?? $id)
                <div class="order-item" data-id="{{ $id }}"><span>{{ $label }}</span><span><button type="button" data-move="up">↑</button> <button type="button" data-move="down">↓</button></span></div>
            @endforeach
        </div>
        <hr style="margin:22px 0;border:0;border-top:1px solid #d8e2ee">
        <div class="publish-note"><strong>{{ $hasPublishedLayout ? 'A live layout already exists.' : 'Your current TechForge-style storefront remains live.' }}</strong><br>Saving makes a private draft. Publishing replaces the public homepage for <code>{{ $company->ecommerce_slug }}.{{ config('ecommerce.storefront_base_domain') }}</code>.</div>
        <form method="post" action="{{ route('ecommerce.admin.layout.publish') }}" style="margin-top:14px">@csrf<button type="submit">Publish storefront</button></form>
    </aside>
</div>

<script>
    (() => {
        const list = document.getElementById('section-order-list');
        const input = document.getElementById('section-order');
        const sync = () => input.value = [...list.querySelectorAll('[data-id]')].map(item => item.dataset.id).join(',');
        list.addEventListener('click', (event) => {
            const button = event.target.closest('[data-move]');
            if (!button) return;
            const item = button.closest('[data-id]');
            if (button.dataset.move === 'up' && item.previousElementSibling) list.insertBefore(item, item.previousElementSibling);
            if (button.dataset.move === 'down' && item.nextElementSibling) list.insertBefore(item.nextElementSibling, item);
            sync();
        });
    })();
</script>
@endsection
