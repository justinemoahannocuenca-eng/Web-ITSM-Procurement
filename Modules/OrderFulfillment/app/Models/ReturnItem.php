<?php

namespace Modules\OrderFulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnItem extends Model
{
    use HasFactory;

    // returns has no client_id column and isn't tenant-scoped, so this
    // model intentionally does NOT use the BelongsToClient trait — that
    // trait's global scope filters on a column this table doesn't have.
    // It still needs the same DB connection BelongsToClient would have set,
    // so that's declared directly here instead.
    protected $connection = 'order_fulfillment';

    protected $table = 'returns';

    protected $fillable = [
        'id',
        'order_id',
        'customer_name',
        'product_name',
        'reason',
        'status',
        'resolution',
        'due_date',
        'address',
        'refund_amount'
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}