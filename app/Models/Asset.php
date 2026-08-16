<?php

namespace App\Models;

use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $id
 * @property string $asset_number
 * @property string $name
 * @property string $category
 * @property string $status
 * @property string|null $serial_number
 * @property string $acquisition_value
 * @property string $currency
 * @property array{site: string, building: string, room: string}|null $location
 * @property array{external_id: string, name: string}|null $supplier
 * @property array{last_completed_at: UTCDateTime, next_due_at: UTCDateTime, interval_days: int}|null $maintenance
 * @property Carbon|null $acquired_at
 * @property Carbon|null $warranty_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'asset_number',
    'name',
    'category',
    'status',
    'serial_number',
    'acquisition_value',
    'currency',
    'location',
    'supplier',
    'maintenance',
    'acquired_at',
    'warranty_until',
])]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_RETIRED = 'retired';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_MAINTENANCE,
        self::STATUS_INACTIVE,
        self::STATUS_RETIRED,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acquisition_value' => 'decimal:2',
            'acquired_at' => 'date',
            'warranty_until' => 'date',
        ];
    }
}
