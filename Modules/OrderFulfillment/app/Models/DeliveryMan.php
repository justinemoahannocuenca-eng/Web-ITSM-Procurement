<?php

namespace Modules\OrderFulfillment\Models;

use Modules\OrderFulfillment\Models\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Model;

class DeliveryMan extends Model
{
    use BelongsToClient;
    protected $table = 'delivery_men';

    // IDs are strings like "DM-FLASH-001", not auto-increment integers.
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // delivery_men has created_at/updated_at columns (confirmed in schema),
    // but nothing in the app currently relies on Eloquent auto-managing them.
    // Leave this false unless you start using ->create() / ->save() and want
    // those columns populated automatically.
    public $timestamps = false;

    const STATUS_AVAILABLE = 'AVAILABLE';
    const STATUS_UNAVAILABLE = 'UNAVAILABLE';

    protected $fillable = [
        'id',
        'name',
        'age',
        'license_num',
        'plate_number',
        'vehicle_type',
        'courier_provider', // confirmed column name in delivery_men table
        'status',
    ];

    /**
     * Scope: only drivers currently free to take a shipment.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * shipments.courier is entered on the order form using
     * customer-facing brand names (e.g. "J&T", "Flash Express"),
     * while delivery_men.courier_provider stores the short internal
     * code (e.g. "JNT", "FLASH"). These are genuinely different
     * strings, not just formatting differences, so add every known
     * variant here as new couriers/spellings show up.
     */
    protected static array $courierAliases = [
        'JNT' => 'JNT',
        'J&T' => 'JNT',
        'J&T EXPRESS' => 'JNT',
        'JNT EXPRESS' => 'JNT',
        'FLASH' => 'FLASH',
        'FLASH EXPRESS' => 'FLASH',
    ];

    /**
     * Map a shipment's courier name to the internal courier_provider
     * code used on delivery_men. Falls back to the trimmed/uppercased
     * input itself if no alias is registered, so unmapped couriers
     * still get an exact-match attempt instead of silently failing.
     */
    public static function normalizeCourier(string $courier): string
    {
        $key = strtoupper(trim($courier));

        return self::$courierAliases[$key] ?? $key;
    }

    /**
     * Scope: only drivers who work for the given courier.
     * $courier is the raw value from shipments.courier — it gets
     * normalized to delivery_men's internal code before matching.
     */
    public function scopeForCourier($query, string $courier)
    {
        return $query->whereRaw('UPPER(TRIM(courier_provider)) = ?', [
            self::normalizeCourier($courier),
        ]);
    }
}