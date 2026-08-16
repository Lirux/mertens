<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $collection): void {
            $collection->unique('asset_number', options: ['name' => 'assets_asset_number_unique']);
            $collection->sparse_and_unique('serial_number', ['name' => 'assets_serial_number_unique']);
            $collection->index([
                'status' => 1,
                'maintenance.next_due_at' => 1,
            ]);

            $collection->jsonSchema(
                [
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
                    'properties' => [
                        '_id' => ['bsonType' => 'objectId'],
                        'asset_number' => ['bsonType' => 'string'],
                        'name' => ['bsonType' => 'string'],
                        'category' => ['bsonType' => 'string'],
                        'status' => [
                            'bsonType' => 'string',
                            'enum' => ['active', 'maintenance', 'out_of_service', 'retired'],
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
                    ],
                ],
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
        Schema::dropIfExists('assets');
    }
};
