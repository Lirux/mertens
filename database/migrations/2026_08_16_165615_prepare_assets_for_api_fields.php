<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\BSON\Decimal128;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $collection): void {
            $collection->jsonSchema(
                $this->assetSchema(
                    statuses: ['active', 'maintenance', 'out_of_service', 'inactive', 'retired'],
                    includeApiFields: true,
                ),
                validationLevel: 'strict',
                validationAction: 'error',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $collection): void {
            $collection->jsonSchema(
                $this->assetSchema(
                    statuses: ['active', 'maintenance', 'out_of_service', 'retired'],
                    includeApiFields: false,
                ),
                validationLevel: 'strict',
                validationAction: 'error',
            );
        });
    }

    /**
     * @param  list<string>  $statuses
     * @return array<string, mixed>
     */
    private function assetSchema(array $statuses, bool $includeApiFields): array
    {
        $properties = [
            '_id' => ['bsonType' => 'objectId'],
            'asset_number' => ['bsonType' => 'string'],
            'name' => ['bsonType' => 'string'],
            'category' => ['bsonType' => 'string'],
            'status' => [
                'bsonType' => 'string',
                'enum' => $statuses,
            ],
            'serial_number' => ['bsonType' => 'string'],
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
                    'last_completed_at' => ['bsonType' => 'date'],
                    'next_due_at' => ['bsonType' => 'date'],
                    'interval_days' => [
                        'bsonType' => 'int',
                        'minimum' => 1,
                    ],
                ],
            ],
            'created_at' => ['bsonType' => 'date'],
            'updated_at' => ['bsonType' => 'date'],
        ];

        if ($includeApiFields) {
            $properties['acquisition_value'] = [
                'bsonType' => 'decimal',
                'minimum' => new Decimal128('0.00'),
            ];
            $properties['currency'] = [
                'bsonType' => 'string',
                'pattern' => '^[A-Z]{3}$',
            ];
        }

        return [
            'bsonType' => 'object',
            'additionalProperties' => false,
            'required' => [
                'asset_number',
                'name',
                'category',
                'status',
                'created_at',
                'updated_at',
            ],
            'properties' => $properties,
        ];
    }
};
