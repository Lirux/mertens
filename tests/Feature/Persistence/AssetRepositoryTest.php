<?php

use App\Models\Asset;
use App\Repositories\AssetRepository;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\BulkWriteException;

test('assets can be created read updated paginated and deleted through the repository', function () {
    $repository = app(AssetRepository::class);

    $asset = $repository->create(assetAttributes());

    $this->assertModelExists($asset);

    expect($asset->id)->toBeString()->toHaveLength(24)
        ->and($repository->find($asset->id)?->asset_number)->toBe('AST-10001')
        ->and($repository->find('invalid-id'))->toBeNull()
        ->and($repository->find(str_repeat('a', 24)))->toBeNull();

    $updatedAsset = $repository->update($asset, [
        'status' => Asset::STATUS_MAINTENANCE,
        'location' => [
            'site' => 'Hauptsitz',
            'building' => 'B',
            'room' => '204',
        ],
        'maintenance' => [
            'last_completed_at' => '2026-08-01T08:30:00+00:00',
            'next_due_at' => '2027-08-01T08:30:00+00:00',
            'interval_days' => 365,
        ],
    ]);

    expect($updatedAsset->status)->toBe(Asset::STATUS_MAINTENANCE)
        ->and($updatedAsset->location['building'])->toBe('B')
        ->and($repository->paginate()->total())->toBe(1);

    $storedAsset = $asset->getConnection()
        ->getCollection($asset->getTable())
        ->findOne(['_id' => new ObjectId($asset->id)]);

    expect($storedAsset['maintenance']['last_completed_at'])->toBeInstanceOf(UTCDateTime::class)
        ->and($storedAsset['maintenance']['next_due_at'])->toBeInstanceOf(UTCDateTime::class)
        ->and($storedAsset['acquisition_value'])->toBeInstanceOf(Decimal128::class)
        ->and((string) $storedAsset['acquisition_value'])->toBe('125000.00');

    expect($repository->delete($updatedAsset))->toBeTrue();
    $this->assertModelMissing($updatedAsset);
});

test('multiple assets without a serial number can be stored', function () {
    $repository = app(AssetRepository::class);

    $repository->create(assetAttributes([
        'asset_number' => 'AST-10002',
        'serial_number' => null,
    ]));

    $repository->create(assetAttributes([
        'asset_number' => 'AST-10003',
        'serial_number' => '',
    ]));

    expect(Asset::query()->count())->toBe(2)
        ->and(Asset::query()->where('serial_number', 'exists', true)->count())->toBe(0);
});

test('duplicate business identifiers are rejected', function (string $field) {
    $repository = app(AssetRepository::class);
    $repository->create(assetAttributes());

    $duplicate = assetAttributes(['asset_number' => 'AST-10002']);

    if ($field === 'asset_number') {
        $duplicate['asset_number'] = 'AST-10001';
        $duplicate['serial_number'] = 'SN-UNIQUE-002';
    }

    expect(fn () => $repository->create($duplicate))
        ->toThrow(BulkWriteException::class);
})->with(['asset_number', 'serial_number']);

test('the collection validator rejects invalid assets', function (array $overrides) {
    $repository = app(AssetRepository::class);

    expect(fn () => $repository->create(assetAttributes($overrides)))
        ->toThrow(BulkWriteException::class);
})->with([
    'invalid status' => [['status' => 'unknown']],
    'legacy status' => [['status' => 'out_of_service']],
    'negative acquisition value' => [['acquisition_value' => '-0.01']],
    'invalid currency' => [['currency' => 'chf']],
    'invalid maintenance interval' => [[
        'maintenance' => ['interval_days' => 0],
    ]],
    'missing required name' => [['name' => null]],
]);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function assetAttributes(array $overrides = []): array
{
    return array_replace_recursive([
        'asset_number' => 'AST-10001',
        'name' => 'CNC Fräsmaschine',
        'category' => 'production',
        'status' => Asset::STATUS_ACTIVE,
        'serial_number' => 'SN-CNC-10001',
        'acquisition_value' => '125000.00',
        'currency' => 'CHF',
        'location' => [
            'site' => 'Hauptsitz',
            'building' => 'A',
            'room' => '101',
        ],
        'supplier' => [
            'external_id' => 'SUP-1000',
            'name' => 'Muster Maschinen AG',
        ],
        'maintenance' => [
            'last_completed_at' => '2026-01-15T08:30:00+00:00',
            'next_due_at' => '2027-01-15T08:30:00+00:00',
            'interval_days' => 365,
        ],
        'acquired_at' => '2023-01-01',
        'warranty_until' => '2028-01-01',
    ], $overrides);
}
