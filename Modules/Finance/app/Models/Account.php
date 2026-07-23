<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\Concerns\BelongsToClient;

class Account extends Model
{
    use BelongsToClient;

    protected $table = 'accounts';
    protected $primaryKey = 'account_id';
    protected $fillable = ['name', 'account_type', 'detail_type', 'balance', 'nexora_client_id'];
}
