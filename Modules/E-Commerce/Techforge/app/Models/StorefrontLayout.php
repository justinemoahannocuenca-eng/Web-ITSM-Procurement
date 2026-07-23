<?php

namespace Modules\Ecommerce\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Modules\Ecommerce\Models\Concerns\BelongsToClient;

class StorefrontLayout extends Model
{
    use BelongsToClient;

    protected $fillable = ['draft_layout', 'published_layout'];

    protected $casts = [
        'draft_layout' => 'array',
        'published_layout' => 'array',
    ];

    public static function defaultFor(Company $company): array
    {
        return [
            'brand_name' => $company->company_name,
            'tagline' => 'Official Nexora storefront',
            'primary_color' => '#ff6b00',
            'accent_color' => '#f59e0b',
            'logo_path' => null,
            'sections' => [
                [
                    'id' => 'hero',
                    'enabled' => true,
                    'title' => 'Products built for your next big move.',
                    'highlight' => 'Shop with confidence.',
                    'body' => 'Explore products that are available from this client store, backed by live inventory availability.',
                    'button_label' => 'Browse products',
                    'button_url' => '#products',
                    'image_path' => null,
                ],
                [
                    'id' => 'featured_listings',
                    'enabled' => true,
                    'title' => 'Featured products',
                    'body' => 'Available now from our current inventory.',
                ],
                [
                    'id' => 'promo',
                    'enabled' => true,
                    'title' => 'Built around your business.',
                    'body' => 'Every listing is tied to approved production data and live warehouse stock.',
                    'button_label' => 'Explore the catalog',
                    'button_url' => '#products',
                ],
                [
                    'id' => 'benefits',
                    'enabled' => true,
                    'title' => 'Why shop with us',
                    'benefit_one' => 'Inventory-aware availability',
                    'benefit_two' => 'Secure checkout',
                    'benefit_three' => 'Order tracking',
                ],
            ],
        ];
    }

    public static function publishedFor(Company $company): array
    {
        $layout = static::query()->first();

        return $layout?->published_layout ?: static::defaultFor($company);
    }

    public static function editableFor(Company $company): array
    {
        $layout = static::query()->first();

        return $layout?->draft_layout ?: $layout?->published_layout ?: static::defaultFor($company);
    }
}
