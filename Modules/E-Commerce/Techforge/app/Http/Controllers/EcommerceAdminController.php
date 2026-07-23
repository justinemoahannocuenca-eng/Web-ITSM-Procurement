<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\StorefrontLayout;
use Modules\Ecommerce\Models\StorefrontListing;
use Modules\Ecommerce\Support\EcommerceClientContext;

class EcommerceAdminController extends Controller
{
    public function login() { return view('ecommerce::admin.login'); }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        if (! Auth::guard('ecommerce_admin')->attempt($credentials)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        return redirect()->route('ecommerce.admin.dashboard');
    }

    public function dashboard()
    {
        $clientId = (int) app(EcommerceClientContext::class)->clientId();
        return view('ecommerce::admin.dashboard', [
            'listingCount' => StorefrontListing::count(),
            'activeListingCount' => StorefrontListing::where('status', 'active')->count(),
            'orderCount' => Order::count(),
            'bomCount' => DB::connection('manufacturing')->table('product_boms')->where('client_id', $clientId)->where('status', 'active')->count(),
            'recentListings' => StorefrontListing::latest()->take(5)->get(),
        ]);
    }

    public function listings() { return view('ecommerce::admin.listings', ['listings' => StorefrontListing::latest()->get()]); }
    public function createListing() { return view('ecommerce::admin.listing-form', ['listing' => new StorefrontListing(), 'boms' => $this->boms()]); }

    public function storeListing(Request $request): RedirectResponse
    {
        $data = $this->listingData($request);
        if ($request->hasFile('image')) $data['image_url'] = $request->file('image')->store('storefront-listings', 'public');
        StorefrontListing::create($data);
        return redirect()->route('ecommerce.admin.listings')->with('success', 'Storefront listing created.');
    }

    public function editListing(StorefrontListing $listing) { return view('ecommerce::admin.listing-form', ['listing' => $listing, 'boms' => $this->boms()]); }

    public function updateListing(Request $request, StorefrontListing $listing): RedirectResponse
    {
        $data = $this->listingData($request);
        if ($request->hasFile('image')) $data['image_url'] = $request->file('image')->store('storefront-listings', 'public');
        $listing->update($data);
        return redirect()->route('ecommerce.admin.listings')->with('success', 'Storefront listing updated.');
    }

    public function destroyListing(StorefrontListing $listing): RedirectResponse
    {
        $listing->delete();
        return redirect()->route('ecommerce.admin.listings')->with('success', 'Storefront listing removed.');
    }

    public function orders() { return view('ecommerce::admin.orders', ['orders' => Order::latest()->paginate(20)]); }

    public function editLayout()
    {
        $company = $this->company();

        return view('ecommerce::admin.layout-editor', [
            'company' => $company,
            'layout' => StorefrontLayout::editableFor($company),
            'hasPublishedLayout' => (bool) StorefrontLayout::query()->first()?->published_layout,
        ]);
    }

    public function saveLayout(Request $request): RedirectResponse
    {
        $company = $this->company();
        $record = StorefrontLayout::query()->first();
        $current = $record?->draft_layout ?: $record?->published_layout ?: StorefrontLayout::defaultFor($company);
        $layout = $this->layoutData($request, $current);

        if ($request->hasFile('logo')) {
            $layout['logo_path'] = $request->file('logo')->store('storefront-layouts', 'public');
        }

        if ($request->hasFile('hero_image')) {
            $layout['sections'] = collect($layout['sections'])->map(function (array $section) use ($request): array {
                if ($section['id'] === 'hero') {
                    $section['image_path'] = $request->file('hero_image')->store('storefront-layouts', 'public');
                }

                return $section;
            })->all();
        }

        if ($record) {
            $record->update(['draft_layout' => $layout]);
        } else {
            StorefrontLayout::create(['draft_layout' => $layout]);
        }

        return redirect()->route('ecommerce.admin.layout.edit')->with('success', 'Draft saved. Preview it, then publish when you are ready.');
    }

    public function previewLayout()
    {
        $company = $this->company();

        return view('ecommerce::storefront', [
            'company' => $company,
            'layout' => StorefrontLayout::editableFor($company),
            'storefrontListings' => StorefrontListing::query()->where('status', 'active')->latest()->take(12)->get(),
            'prebuiltPcs' => [],
            'customConfigs' => [],
            'preview' => true,
        ]);
    }

    public function publishLayout(): RedirectResponse
    {
        $company = $this->company();
        $record = StorefrontLayout::query()->first();

        if (! $record?->draft_layout) {
            return redirect()->route('ecommerce.admin.layout.edit')->withErrors([
                'layout' => 'Save a layout draft before publishing it.',
            ]);
        }

        $record->update(['published_layout' => $record->draft_layout]);

        return redirect()->route('ecommerce.admin.layout.edit')->with('success', 'Your storefront layout is live on '.$company->ecommerce_slug.'.'.config('ecommerce.storefront_base_domain').'.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('ecommerce_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('ecommerce.admin.login');
    }

    private function boms()
    {
        return DB::connection('manufacturing')->table('product_boms')->where('client_id', app(EcommerceClientContext::class)->clientId())->where('status', 'active')->orderBy('name')->get();
    }

    private function listingData(Request $request): array
    {
        return $request->validate([
            'bom_id' => ['required', 'integer'], 'sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'], 'status' => ['required', 'in:draft,active,archived'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    private function company(): Company
    {
        $company = auth('ecommerce_admin')->user()?->getCompany();
        abort_unless($company instanceof Company && (int) $company->id === (int) app(EcommerceClientContext::class)->clientId(), 403);

        return $company;
    }

    private function layoutData(Request $request, array $current): array
    {
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'section_order' => ['required', 'string', 'max:100'],
            'hero_title' => ['nullable', 'string', 'max:160'],
            'hero_highlight' => ['nullable', 'string', 'max:100'],
            'hero_body' => ['nullable', 'string', 'max:600'],
            'hero_button_label' => ['nullable', 'string', 'max:60'],
            'hero_button_url' => ['nullable', 'string', 'max:255'],
            'listings_title' => ['nullable', 'string', 'max:100'],
            'listings_body' => ['nullable', 'string', 'max:300'],
            'promo_title' => ['nullable', 'string', 'max:140'],
            'promo_body' => ['nullable', 'string', 'max:600'],
            'promo_button_label' => ['nullable', 'string', 'max:60'],
            'promo_button_url' => ['nullable', 'string', 'max:255'],
            'benefits_title' => ['nullable', 'string', 'max:100'],
            'benefit_one' => ['nullable', 'string', 'max:100'],
            'benefit_two' => ['nullable', 'string', 'max:100'],
            'benefit_three' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $allowedSections = ['hero', 'featured_listings', 'promo', 'benefits'];
        $order = array_values(array_unique(array_filter(explode(',', $validated['section_order']), fn (string $id): bool => in_array($id, $allowedSections, true))));
        $order = array_values(array_unique([...$order, ...$allowedSections]));
        $existing = collect($current['sections'] ?? [])->keyBy('id');
        $section = fn (string $id): array => (array) $existing->get($id, ['id' => $id]);

        $hero = $section('hero');
        $hero = [...$hero, 'id' => 'hero', 'enabled' => $request->boolean('hero_enabled'), 'title' => $validated['hero_title'] ?: 'Products built for your next big move.', 'highlight' => $validated['hero_highlight'] ?: 'Shop with confidence.', 'body' => $validated['hero_body'] ?: '', 'button_label' => $validated['hero_button_label'] ?: 'Browse products', 'button_url' => $this->safeStorefrontLink($validated['hero_button_url'] ?? '#products')];

        $listings = $section('featured_listings');
        $listings = [...$listings, 'id' => 'featured_listings', 'enabled' => $request->boolean('featured_listings_enabled'), 'title' => $validated['listings_title'] ?: 'Featured products', 'body' => $validated['listings_body'] ?: 'Available now from our current inventory.'];

        $promo = $section('promo');
        $promo = [...$promo, 'id' => 'promo', 'enabled' => $request->boolean('promo_enabled'), 'title' => $validated['promo_title'] ?: 'Built around your business.', 'body' => $validated['promo_body'] ?: '', 'button_label' => $validated['promo_button_label'] ?: 'Explore the catalog', 'button_url' => $this->safeStorefrontLink($validated['promo_button_url'] ?? '#products')];

        $benefits = $section('benefits');
        $benefits = [...$benefits, 'id' => 'benefits', 'enabled' => $request->boolean('benefits_enabled'), 'title' => $validated['benefits_title'] ?: 'Why shop with us', 'benefit_one' => $validated['benefit_one'] ?: 'Inventory-aware availability', 'benefit_two' => $validated['benefit_two'] ?: 'Secure checkout', 'benefit_three' => $validated['benefit_three'] ?: 'Order tracking'];
        $sectionsById = [
            'hero' => $hero,
            'featured_listings' => $listings,
            'promo' => $promo,
            'benefits' => $benefits,
        ];

        return [
            'brand_name' => $validated['brand_name'],
            'tagline' => $validated['tagline'] ?: 'Official Nexora storefront',
            'primary_color' => $validated['primary_color'],
            'accent_color' => $validated['accent_color'],
            'logo_path' => $current['logo_path'] ?? null,
            'sections' => array_map(fn (string $id): array => $sectionsById[$id], $order),
        ];
    }

    private function safeStorefrontLink(?string $url): string
    {
        $url = trim((string) $url);

        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return $url;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL)
            ? $url
            : '#products';
    }
}
