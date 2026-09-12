<?php

use Illuminate\Support\Facades\DB;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

test('existing completed maintenance is backfilled into the new history schema', function () {
    $this->artisan('migrate:fresh', [
        '--path' => [
            'database/migrations/2026_08_15_055454_create_assets_collection.php',
            'database/migrations/2026_08_16_165615_prepare_assets_for_api_fields.php',
            'database/migrations/2026_08_16_165619_backfill_assets_for_api_fields.php',
            'database/migrations/2026_08_16_165623_finalize_assets_api_schema.php',
            'database/migrations/2026_08_16_174229_add_asset_sync_index.php',
            'database/migrations/2026_08_17_053751_extend_asset_maintenance_for_web_app.php',
        ],
        '--force' => true,
    ])->assertSuccessful();

    $database = DB::connection('mongodb')->getDatabase();
    $completedAt = new UTCDateTime(strtotime('2026-02-15') * 1000);
    $nextDueAt = new UTCDateTime(strtotime('2027-02-15') * 1000);
    $now = new UTCDateTime;

    $database->assets->insertOne([
        'asset_number' => 'AST-HISTORY-001',
        'name' => 'Bestandsasset',
        'category' => 'production',
        'status' => 'active',
        'acquisition_value' => new Decimal128('1000.00'),
        'currency' => 'CHF',
        'maintenance' => [
            'last_completed_at' => $completedAt,
            'next_due_at' => $nextDueAt,
            'interval_days' => 365,
            'note' => 'Vorhandene Wartungsnotiz',
        ],
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->artisan('migrate', [
        '--path' => [
            'database/migrations/2026_08_17_075039_add_maintenance_history_to_assets.php',
            'database/migrations/2026_08_17_075044_backfill_asset_maintenance_history.php',
        ],
        '--force' => true,
    ])->assertSuccessful();

    $asset = $database->assets->findOne(['asset_number' => 'AST-HISTORY-001']);
    $history = $asset['maintenance_history'];
    $entry = $history[0];
    $assetCollectionInfo = iterator_to_array($database->listCollections([
        'filter' => ['name' => 'assets'],
    ]), false)[0];
    $assetSchema = $assetCollectionInfo->getOptions()['validator']['$jsonSchema'];

    expect($history)->toHaveCount(1)
        ->and($entry['_id'])->toBeInstanceOf(ObjectId::class)
        ->and($entry['completed_at'])->toEqual($completedAt)
        ->and($entry['next_due_at'])->toEqual($nextDueAt)
        ->and($entry['interval_days'])->toBe(365)
        ->and($entry['status_after'])->toBe('active')
        ->and($entry['note'])->toBe('Vorhandene Wartungsnotiz')
        ->and($entry['recorded_by']['id'])->toBeNull()
        ->and($entry['recorded_by']['name'])->toBe('Systemmigration')
        ->and($entry['recorded_at'])->toBeInstanceOf(UTCDateTime::class)
        ->and($assetSchema['properties']['maintenance_history']['bsonType'])->toBe('array')
        ->and($assetSchema['properties']['maintenance_history']['items']['required'])
        ->toContain('completed_at', 'recorded_by', 'recorded_at');
});
