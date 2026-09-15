<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Laravel\Connection;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        $assets = $this->assets();
        $recordedAt = new UTCDateTime;
        $existingAssets = $assets->find(
            [
                'maintenance.last_completed_at' => ['$type' => 'date'],
                'maintenance_history.0' => ['$exists' => false],
            ],
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']],
        );

        foreach ($existingAssets as $asset) {
            if (! is_array($asset)) {
                throw new RuntimeException('The maintenance backfill requires array documents.');
            }

            $maintenance = $asset['maintenance'];

            $assets->updateOne(
                ['_id' => $asset['_id']],
                ['$set' => [
                    'maintenance_history' => [[
                        '_id' => new ObjectId,
                        'completed_at' => $maintenance['last_completed_at'],
                        'next_due_at' => $maintenance['next_due_at'],
                        'interval_days' => $maintenance['interval_days'],
                        'status_after' => $asset['status'],
                        'note' => $maintenance['note'] ?? null,
                        'recorded_by' => [
                            'id' => null,
                            'name' => 'Systemmigration',
                        ],
                        'recorded_at' => $recordedAt,
                    ]],
                ]],
            );
        }
    }

    public function down(): void
    {
        $this->assets()->updateMany(
            [],
            ['$pull' => [
                'maintenance_history' => [
                    'recorded_by.id' => null,
                    'recorded_by.name' => 'Systemmigration',
                ],
            ]],
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
