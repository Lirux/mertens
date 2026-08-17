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
        Schema::table('assets', function (Blueprint $collection): void {
            $collection->index(
                ['updated_at' => 1, '_id' => 1],
                options: ['name' => 'assets_updated_at_id_sync'],
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $collection): void {
            $collection->dropIndexIfExists('assets_updated_at_id_sync');
        });
    }
};
