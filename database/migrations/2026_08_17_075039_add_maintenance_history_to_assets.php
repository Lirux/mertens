<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MongoDB\BSON\Decimal128;
use MongoDB\Collection;
use MongoDB\Laravel\Connection;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::table('assets', function (Blueprint $collection): void {
            $collection->jsonSchema(
                $this->assetSchema(includeMaintenanceHistory: true),
                validationLevel: 'strict',
                validationAction: 'error',
            );
        });
    }

    public function down(): void
    {
        $this->assets()->updateMany(
            [],
            ['$unset' => ['maintenance_history' => '']],
        );

        Schema::table('assets', function (Blueprint $collection): void {
            $collection->jsonSchema(
                $this->assetSchema(includeMaintenanceHistory: false),
                validationLevel: 'strict',
                validationAction: 'error',
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function assetSchema(bool $includeMaintenanceHistory): array
    {
        $properties = [
            '_id' => ['bsonType' => 'objectId'],
            'asset_number' => ['bsonType' => 'string'],
            'name' => ['bsonType' => 'string'],
            'category' => ['bsonType' => 'string'],
            'status' => [
                'bsonType' => 'string',
                'enum' => ['active', 'maintenance', 'inactive', 'retired'],
            ],
            'serial_number' => ['bsonType' => 'string'],
            'acquisition_value' => [
                'bsonType' => 'decimal',
                'minimum' => new Decimal128('0.00'),
            ],
            'currency' => ['bsonType' => 'string', 'pattern' => '^[A-Z]{3}$'],
            'acquired_at' => ['bsonType' => ['date', 'null']],
            'warranty_until' => ['bsonType' => ['date', 'null']],
            'location' => [
                'bsonType' => 'object',
                'additionalProperties' => false,
                'required' => ['site', 'building', 'room'],
                'properties' => [
                    'site' => ['bsonType' => 'string'],
                    'building' => ['bsonType' => 'string'],
                    'room' => ['bsonType' => 'string'],
                ],
            ],
            'supplier' => [
                'bsonType' => 'object',
                'additionalProperties' => false,
                'required' => ['external_id', 'name'],
                'properties' => [
                    'external_id' => ['bsonType' => 'string'],
                    'name' => ['bsonType' => 'string'],
                ],
            ],
            'maintenance' => [
                'bsonType' => 'object',
                'additionalProperties' => false,
                'required' => ['last_completed_at', 'next_due_at', 'interval_days'],
                'properties' => [
                    'last_completed_at' => ['bsonType' => ['date', 'null']],
                    'next_due_at' => ['bsonType' => 'date'],
                    'interval_days' => ['bsonType' => 'int', 'minimum' => 1],
                    'note' => ['bsonType' => ['string', 'null']],
                ],
            ],
            'created_at' => ['bsonType' => 'date'],
            'updated_at' => ['bsonType' => 'date'],
        ];

        if ($includeMaintenanceHistory) {
            $properties['maintenance_history'] = $this->maintenanceHistorySchema();
        }

        return [
            'bsonType' => 'object',
            'additionalProperties' => false,
            'required' => [
                'asset_number',
                'name',
                'category',
                'status',
                'acquisition_value',
                'currency',
                'created_at',
                'updated_at',
            ],
            'properties' => $properties,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function maintenanceHistorySchema(): array
    {
        return [
            'bsonType' => 'array',
            'items' => [
                'bsonType' => 'object',
                'additionalProperties' => false,
                'required' => [
                    '_id',
                    'completed_at',
                    'next_due_at',
                    'interval_days',
                    'status_after',
                    'note',
                    'recorded_by',
                    'recorded_at',
                ],
                'properties' => [
                    '_id' => ['bsonType' => 'objectId'],
                    'completed_at' => ['bsonType' => 'date'],
                    'next_due_at' => ['bsonType' => 'date'],
                    'interval_days' => ['bsonType' => 'int', 'minimum' => 1],
                    'status_after' => [
                        'bsonType' => 'string',
                        'enum' => ['active', 'maintenance', 'inactive', 'retired'],
                    ],
                    'note' => ['bsonType' => ['string', 'null']],
                    'recorded_by' => [
                        'bsonType' => 'object',
                        'additionalProperties' => false,
                        'required' => ['id', 'name'],
                        'properties' => [
                            'id' => ['bsonType' => ['string', 'null']],
                            'name' => ['bsonType' => 'string'],
                        ],
                    ],
                    'recorded_at' => ['bsonType' => 'date'],
                ],
            ],
        ];
    }

    private function assets(): Collection
    {
        $connection = DB::connection('mongodb');

        if (! $connection instanceof Connection) {
            throw new RuntimeException('The asset migration requires the MongoDB connection.');
        }

        return $connection->getCollection('assets');
    }
};
