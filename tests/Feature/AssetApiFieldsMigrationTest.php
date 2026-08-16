<?php

use Illuminate\Support\Facades\DB;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\UTCDateTime;

test('legacy assets are upgraded to the final api persistence schema', function () {
    $this->artisan('migrate:fresh', [
        '--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/0001_01_01_000001_create_cache_table.php',
            'database/migrations/0001_01_01_000002_create_jobs_table.php',
            'database/migrations/2026_08_15_055454_create_assets_collection.php',
        ],
        '--force' => true,
    ])->assertSuccessful();

    $database = DB::connection('mongodb')->getDatabase();
    $assetCollectionInfo = iterator_to_array($database->listCollections([
        'filter' => ['name' => 'assets'],
    ]), false)[0];
    $assetSchema = $assetCollectionInfo->getOptions()['validator']['$jsonSchema'];

    expect($assetSchema['properties']['status']['enum'])->toBe([
        'active',
        'maintenance',
        'out_of_service',
        'retired',
    ])
        ->and($assetSchema['properties'])->not->toHaveKey('acquisition_value');

    $assets = $database->assets;
    $now = new UTCDateTime;

    $assets->insertOne([
        'asset_number' => 'AST-LEGACY-001',
        'name' => 'Legacy asset',
        'category' => 'production',
        'status' => 'out_of_service',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $this->artisan('migrate', ['--force' => true])->assertSuccessful();

    $asset = $assets->findOne(['asset_number' => 'AST-LEGACY-001']);

    expect($asset)->not->toBeNull()
        ->and($asset['status'])->toBe('inactive')
        ->and($asset['acquisition_value'])->toBeInstanceOf(Decimal128::class)
        ->and((string) $asset['acquisition_value'])->toBe('0.00')
        ->and($asset['currency'])->toBe('CHF');
});
