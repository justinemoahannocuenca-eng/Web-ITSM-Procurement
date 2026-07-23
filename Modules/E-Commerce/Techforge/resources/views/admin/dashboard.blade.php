@extends('ecommerce::admin.layout', ['title' => 'E-commerce Overview', 'heading' => 'E-commerce Overview'])

@section('content')
<div class="grid">
    <div class="card"><span class="muted">Manufacturing BOMs available</span><div class="metric">{{ $bomCount }}</div></div>
    <div class="card"><span class="muted">Storefront Listings</span><div class="metric">{{ $listingCount }}</div></div>
    <div class="card"><span class="muted">Live Listings</span><div class="metric">{{ $activeListingCount }}</div></div>
    <div class="card"><span class="muted">Storefront Orders</span><div class="metric">{{ $orderCount }}</div></div>
</div>
<section class="card" style="margin-top:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px"><h2>Recent listings</h2><span><a class="button alt" href="{{ route('ecommerce.admin.layout.edit') }}">Edit storefront</a> <a class="button" href="{{ route('ecommerce.admin.listings.create') }}">+ Add listing</a></span></div>
    <table><thead><tr><th>Name</th><th>Status</th><th>Available</th><th>Price</th></tr></thead><tbody>
        @forelse ($recentListings as $listing)
            <tr><td>{{ $listing->name }}</td><td>{{ ucfirst($listing->status) }}</td><td>{{ $listing->available_quantity }}</td><td>&#8369;{{ number_format((float) $listing->price, 2) }}</td></tr>
        @empty
            <tr><td colspan="4" class="muted">Manufacturing must create an active BOM before a listing can be added.</td></tr>
        @endforelse
    </tbody></table>
</section>
@endsection
