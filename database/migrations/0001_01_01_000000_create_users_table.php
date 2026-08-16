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
        Schema::create('users', function (Blueprint $collection): void {
            $collection->unique('email', options: ['name' => 'users_email_unique']);

            $collection->jsonSchema(
                [
                    'bsonType' => 'object',
                    'required' => ['name', 'email', 'password', 'created_at', 'updated_at'],
                    'properties' => [
                        '_id' => ['bsonType' => 'objectId'],
                        'name' => ['bsonType' => 'string'],
                        'email' => ['bsonType' => 'string'],
                        'email_verified_at' => ['bsonType' => ['date', 'null']],
                        'password' => ['bsonType' => 'string'],
                        'remember_token' => ['bsonType' => ['string', 'null']],
                        'two_factor_secret' => ['bsonType' => ['string', 'null']],
                        'two_factor_recovery_codes' => ['bsonType' => ['string', 'null']],
                        'two_factor_confirmed_at' => ['bsonType' => ['date', 'null']],
                        'created_at' => ['bsonType' => 'date'],
                        'updated_at' => ['bsonType' => 'date'],
                    ],
                ],
                validationLevel: 'strict',
                validationAction: 'error',
            );
        });

        Schema::create('password_reset_tokens', function (Blueprint $collection): void {
            $collection->unique('email', options: ['name' => 'password_reset_tokens_email_unique']);
            $collection->index('created_at');
        });

        Schema::create('sessions', function (Blueprint $collection): void {
            $collection->index('user_id');
            $collection->index('last_activity');
            $collection->expire('expires_at', 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
