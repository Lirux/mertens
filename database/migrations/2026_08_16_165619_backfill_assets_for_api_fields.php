<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\Decimal128;
use MongoDB\Collection;
use MongoDB\Laravel\Connection;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $assets = $this->assets();

        $assets->updateMany(
            ['status' => 'out_of_service'],
            ['$set' => ['status' => 'inactive']],
        );
        $assets->updateMany(
            ['acquisition_value' => ['$exists' => false]],
            ['$set' => ['acquisition_value' => new Decimal128('0.00')]],
        );
        $assets->updateMany(
            ['currency' => ['$exists' => false]],
            ['$set' => ['currency' => 'CHF']],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $assets = $this->assets();

        $assets->updateMany(
            ['status' => 'inactive'],
            ['$set' => ['status' => 'out_of_service']],
        );
        $assets->updateMany(
            [],
            ['$unset' => ['acquisition_value' => '', 'currency' => '']],
        );
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
