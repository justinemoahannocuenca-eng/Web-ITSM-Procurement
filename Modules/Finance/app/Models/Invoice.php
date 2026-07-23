<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\Concerns\BelongsToClient;

class Invoice extends Model
{
    use BelongsToClient;

    protected $table = 'invoice';
    protected $primaryKey = 'invoice_id';
    protected $fillable = [
        'client_id', 'issue_date', 'due_date', 'invoice_amount', 'discount', 'shipping_fee',
        'paid_amount', 'payment_method', 'reference_number', 'payment_details',
        'payment_status', 'status', 'payment_date', 'order_id', 'nexora_client_id',
    ];
    protected $casts = [
        'issue_date' => 'date', 'due_date' => 'date', 'payment_date' => 'date',
        'invoice_amount' => 'decimal:2', 'discount' => 'decimal:2',
        'shipping_fee' => 'decimal:2', 'paid_amount' => 'decimal:2',
    ];
}
