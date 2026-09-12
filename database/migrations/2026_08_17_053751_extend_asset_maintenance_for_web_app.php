<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\BSON\Decimal128;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::table('assets', function (Blueprint $collection): void {
            $collection->index('category', options: ['name' => 'assets_category_index']);
            $collection->index(
                'maintenance.next_due_at',
                options: ['name' => 'assets_maintenance_next_due_at_index'],
            );
            $collection->jsonSchema(
                $this->assetSchema(includeWebMaintenanceFields: true),
                validationLevel: 'strict',
                validationAction: 'error',
            );
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $collection): void {
            $collection->dropIndexIfExists('assets_category_index');
            $collection->dropIndexIfExists('assets_maintenance_next_due_at_index');
            $collection->jsonSchema(
                $this->assetSchema(includeWebMaintenanceFields: false),
                validationLevel: 'strict',
                validationAction: 'error',
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function assetSchema(bool $includeWebMaintenanceFields): array
    {
        $maintenanceProperties = [
            'last_completed_at' => [
                'bsonType' => $includeWebMaintenanceFields ? ['date', 'null'] : 'date',
            ],
            'next_due_at' => ['bsonType' => 'date'],
            'interval_days' => ['bsonType' => 'int', 'minimum' => 1],
        ];

        if ($includeWebMaintenanceFields) {
            $maintenanceProperties['note'] = ['bsonType' => ['string', 'null']];
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
            'properties' => [
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
                    'properties' => $maintenanceProperties,
                ],
                'created_at' => ['bsonType' => 'date'],
                'updated_at' => ['bsonType' => 'date'],
            ],
        ];
    }
};
