<?php

namespace Modules\Inventory\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Procurement extends Model
{
    protected $connection = 'procurement';
    protected $table = 'deliveries';

    protected $fillable = [
        'shipment_number',
        'purchase_order_id',
        'supplier_id',
        'stage',
        'status',
        'qty',
        'qty_expected',
        'items',
        'started_at',
        'remarks',
        'delivery_date',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'started_at' => 'datetime',
    ];

    public function getSupplierProduct(): ?object
    {
        $poItem = DB::connection('procurement')
            ->table('purchase_order_items')
            ->where('purchase_order_id', $this->purchase_order_id)
            ->first();

        if (!$poItem) {
            return null;
        }

        $sku = null;

        if ($poItem->supplier_product_id) {
            $supplierProduct = DB::connection('procurement')
                ->table('supplier_products')
                ->where('id', $poItem->supplier_product_id)
                ->first();

            if ($supplierProduct) {
                $sku = $supplierProduct->sku;
            }
        }

        if (!$sku) {
            $productName = trim(preg_replace('/\s*@\s*.*$/', '', $poItem->name));
            $supplierProduct = DB::connection('procurement')
                ->table('supplier_products')
                ->where('name', $productName)
                ->orWhere('name', 'ILIKE', $productName)
                ->first();

            if ($supplierProduct) {
                $sku = $supplierProduct->sku;
            }
        }

        if (!$sku) {
            $sku = 'AUTO-' . strtoupper(Str::random(8));
        }

        $cleanName = trim(preg_replace('/\s*@\s*.*$/', '', $poItem->name));

        return (object) [
            'item_name' => $cleanName,
            'qty' => $poItem->qty,
            'unit_price' => $poItem->unit_price,
            'sku' => $sku,
        ];
    }
}

