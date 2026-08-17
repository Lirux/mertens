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
        Schema::create('personal_access_tokens', function (Blueprint $collection): void {
            $collection->unique('token', options: ['name' => 'personal_access_tokens_token_unique']);
            $collection->index(
                ['tokenable_type' => 1, 'tokenable_id' => 1],
                options: ['name' => 'personal_access_tokens_tokenable_index'],
            );
            $collection->index('expires_at', options: ['name' => 'personal_access_tokens_expires_at_index']);

            $collection->jsonSchema(
                [
                    'bsonType' => 'object',
                    'additionalProperties' => false,
                    'required' => [
                        'tokenable_type',
                        'tokenable_id',
                        'name',
                        'token',
                        'abilities',
                        'created_at',
                        'updated_at',
                    ],
                    'properties' => [
                        '_id' => ['bsonType' => 'objectId'],
                        'tokenable_type' => ['bsonType' => 'string'],
                        'tokenable_id' => ['bsonType' => ['objectId', 'string']],
                        'name' => ['bsonType' => 'string'],
                        'token' => [
                            'bsonType' => 'string',
                            'pattern' => '^[a-f0-9]{64}$',
                        ],
                        'abilities' => ['bsonType' => ['string', 'null']],
                        'last_used_at' => ['bsonType' => ['date', 'null']],
                        'expires_at' => ['bsonType' => ['date', 'null']],
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
        Schema::dropIfExists('personal_access_tokens');
    }
};
