<?php

namespace Modules\OrderFulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    // order_items has no client_id column and isn't tenant-scoped, so this
    // model intentionally does NOT use the BelongsToClient trait — that
    // trait's global scope filters on a column this table doesn't have.
    // It still needs the same DB connection BelongsToClient would have set,
    // so that's declared directly here instead.
    protected $connection = 'order_fulfillment';

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_name',
        'qty',
        'product_amount',
    ];

    protected $casts = [
        'qty' => 'integer',
        'product_amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function getLineTotalAttribute()
    {
        return $this->qty * $this->product_amount;
    }
}