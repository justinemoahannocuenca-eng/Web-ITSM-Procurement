<?php

namespace Modules\Inventory\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait BelongsToClient
{
    public function getConnectionName(): ?string
    {
        return 'inventory';
    }

    protected static function bootBelongsToClient(): void
    {
        static::addGlobalScope('client', function (Builder $query): void {
            if (! Schema::connection('inventory')->hasColumn($query->getModel()->getTable(), 'client_id')) {
                // Never show legacy standalone Inventory data to a client
                // before the owner upgrades the table with its client key.
                $query->whereRaw('1 = 0');

                return;
            }

            if ($clientId = session('employee_client_id')) {
                $query->where($query->getModel()->getTable().'.client_id', $clientId);
            }
        });

        static::creating(function ($model): void {
            if (! $model->client_id && ($clientId = session('employee_client_id'))) {
                $model->client_id = $clientId;
            }
        });
    }
}
