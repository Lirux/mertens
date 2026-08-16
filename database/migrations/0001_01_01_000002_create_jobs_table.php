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
        Schema::create('jobs', function (Blueprint $collection): void {
            $collection->index('queue');
            $collection->index([
                'queue' => 1,
                'reserved' => 1,
                'available_at' => 1,
            ]);
            $collection->index([
                'queue' => 1,
                'reserved_at' => 1,
            ]);
        });

        Schema::create('job_batches');

        Schema::create('failed_jobs', function (Blueprint $collection): void {
            $collection->unique('uuid', options: ['name' => 'failed_jobs_uuid_unique']);
            $collection->index([
                'connection' => 1,
                'queue' => 1,
                'failed_at' => -1,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
    }
};
