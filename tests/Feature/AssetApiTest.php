<?php

use App\Models\Asset;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

test('stores Sanctum bearer tokens as hashes in MongoDB', function () {
    $plainTextToken = assetApiToken(['assets:read']);
    $token = PersonalAccessToken::query()->sole();

    expect($plainTextToken)
        ->toStartWith($token->getKey().'|')
        ->and($token->token)->toHaveLength(64)
        ->and($plainTextToken)->not->toContain($token->token)
        ->and($token->abilities)->toBe(['assets:read']);
});

test('all asset routes reject missing and invalid bearer tokens', function () {
    $assetId = str_repeat('a', 24);
    $requests = [
        ['GET', '/api/v1/assets', []],
        ['POST', '/api/v1/assets', assetApiPayload()],
        ['GET', "/api/v1/assets/{$assetId}", []],
        ['PUT', "/api/v1/assets/{$assetId}", assetApiPayload()],
        ['DELETE', "/api/v1/assets/{$assetId}", []],
    ];

    foreach ($requests as [$method, $uri, $data]) {
        $this->json($method, $uri, $data)->assertUnauthorized();
    }

    foreach ($requests as [$method, $uri, $data]) {
        $this->withToken('invalid-token')->json($method, $uri, $data)->assertUnauthorized();
    }
});

test('token abilities separate read and write access', function () {
    $readToken = assetApiToken(['assets:read']);

    $this->withToken($readToken)
        ->getJson('/api/v1/assets')
        ->assertOk();

    $this->withToken($readToken)
        ->postJson('/api/v1/assets', assetApiPayload())
        ->assertForbidden();

    $writeToken = assetApiToken(['assets:write']);
    Auth::forgetGuards();

    $this->withToken($writeToken)
        ->postJson('/api/v1/assets', assetApiPayload())
        ->assertCreated();

    $this->withToken($writeToken)
        ->getJson('/api/v1/assets')
        ->assertForbidden();
});

test('assets can be created read fully updated and deleted', function () {
    $token = assetApiToken(['assets:read', 'assets:write']);
    $payload = assetApiPayload();

    $created = $this->withToken($token)
        ->postJson('/api/v1/assets', $payload)
        ->assertCreated();

    $assetId = $created->json('id');
    $expectedFields = [
        'id',
        'inventoryNumber',
        'serialNumber',
        'name',
        'category',
        'status',
        'location',
        'acquisitionDate',
        'acquisitionValue',
        'currency',
        'updatedAt',
    ];

    expect($assetId)->toBeString()->toHaveLength(24)
        ->and(array_keys($created->json()))->toBe($expectedFields)
        ->and($created->json('acquisitionValue'))->toBeFloat()
        ->and($created->json())->not->toHaveKeys(['supplier', 'maintenance', 'asset_number']);

    $created->assertJson([
        'id' => $assetId,
        'inventoryNumber' => 'AST-API-0001',
        'serialNumber' => 'SN-API-0001',
        'name' => 'CNC Fräsmaschine',
        'category' => 'production',
        'status' => 'active',
        'location' => [
            'site' => 'Hauptsitz',
            'building' => 'A',
            'room' => '101',
        ],
        'acquisitionDate' => '2023-01-15',
        'acquisitionValue' => 125000.5,
        'currency' => 'CHF',
    ])->assertJsonPath(
        'updatedAt',
        fn (string $value): bool => preg_match(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/',
            $value,
        ) === 1,
    );

    $this->withToken($token)
        ->getJson("/api/v1/assets/{$assetId}")
        ->assertOk()
        ->assertJson(['id' => $assetId, 'inventoryNumber' => 'AST-API-0001']);

    $updatedPayload = assetApiPayload([
        'inventoryNumber' => 'AST-API-0002',
        'serialNumber' => null,
        'name' => 'CNC Fräsmaschine 2',
        'status' => 'maintenance',
        'location' => [
            'site' => 'Werk Nord',
            'building' => 'B',
            'room' => '204',
        ],
        'acquisitionDate' => '2024-02-29',
        'acquisitionValue' => 99500,
        'currency' => 'EUR',
    ]);

    $this->withToken($token)
        ->putJson("/api/v1/assets/{$assetId}", $updatedPayload)
        ->assertOk()
        ->assertJson([
            'id' => $assetId,
            'inventoryNumber' => 'AST-API-0002',
            'serialNumber' => null,
            'name' => 'CNC Fräsmaschine 2',
            'status' => 'maintenance',
            'acquisitionDate' => '2024-02-29',
            'acquisitionValue' => 99500.0,
            'currency' => 'EUR',
        ]);

    $this->withToken($token)
        ->deleteJson("/api/v1/assets/{$assetId}")
        ->assertNoContent();

    $this->withToken($token)
        ->getJson("/api/v1/assets/{$assetId}")
        ->assertNotFound();
});

test('list defaults to fifty assets and zero offset', function () {
    $token = assetApiToken(['assets:read']);
    Asset::factory()->count(51)->create();

    $response = $this->withToken($token)
        ->getJson('/api/v1/assets')
        ->assertOk();

    expect($response->json())->toHaveCount(50)
        ->and($response->json())->toBeList();
});

test('list filters by updated time and applies stable offset and limit sorting', function () {
    $token = assetApiToken(['assets:read']);
    $assets = collect([
        Asset::factory()->create(['asset_number' => 'AST-SORT-1']),
        Asset::factory()->create(['asset_number' => 'AST-SORT-2']),
        Asset::factory()->create(['asset_number' => 'AST-SORT-3']),
        Asset::factory()->create(['asset_number' => 'AST-SORT-4']),
    ]);
    $timestamps = [
        '2026-08-16T10:00:00+00:00',
        '2026-08-16T10:00:00+00:00',
        '2026-08-16T11:00:00+00:00',
        '2026-08-16T12:00:00+00:00',
    ];
    $collection = Asset::query()->getConnection()->getCollection('assets');

    foreach ($assets as $index => $asset) {
        $collection->updateOne(
            ['_id' => new ObjectId($asset->id)],
            ['$set' => ['updated_at' => new UTCDateTime(CarbonImmutable::parse($timestamps[$index]))]],
        );
    }

    $stableResponse = $this->withToken($token)
        ->getJson('/api/v1/assets?updatedSince=2026-08-16T10%3A00%3A00%2B00%3A00')
        ->assertOk();

    expect(collect($stableResponse->json())->pluck('id')->all())->toBe($assets->pluck('id')->all());

    $pagedResponse = $this->withToken($token)
        ->getJson('/api/v1/assets?updatedSince=2026-08-16T10%3A30%3A00%2B00%3A00&limit=1&offset=1')
        ->assertOk();

    expect($pagedResponse->json())->toHaveCount(1)
        ->and($pagedResponse->json('0.id'))->toBe($assets[3]->id);
});

test('API output timestamps can be reused as updated since filters', function () {
    $token = assetApiToken(['assets:read', 'assets:write']);

    $created = $this->withToken($token)
        ->postJson('/api/v1/assets', assetApiPayload())
        ->assertCreated();

    $updatedSince = rawurlencode($created->json('updatedAt'));

    $this->withToken($token)
        ->getJson("/api/v1/assets?updatedSince={$updatedSince}")
        ->assertOk()
        ->assertJsonFragment(['id' => $created->json('id')]);
});

test('invalid list parameters return bad request responses', function (string $query) {
    $token = assetApiToken(['assets:read']);

    $this->withToken($token)
        ->getJson('/api/v1/assets?'.$query)
        ->assertBadRequest()
        ->assertJsonStructure(['message', 'errors']);
})->with([
    'invalid timestamp' => 'updatedSince=not-a-date',
    'date without time' => 'updatedSince=2026-08-16',
    'zero limit' => 'limit=0',
    'excessive limit' => 'limit=101',
    'negative offset' => 'offset=-1',
]);

test('invalid asset data returns unprocessable entity responses', function (array $overrides, string $errorKey) {
    $token = assetApiToken(['assets:write']);

    $this->withToken($token)
        ->postJson('/api/v1/assets', assetApiPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errorKey);
})->with([
    'missing inventory number' => [['inventoryNumber' => null], 'inventoryNumber'],
    'invalid status' => [['status' => 'out_of_service'], 'status'],
    'incomplete location' => [['location' => ['site' => 'HQ', 'building' => 'A']], 'location.room'],
    'unexpected location field' => [[
        'location' => [
            'site' => 'HQ',
            'building' => 'A',
            'room' => '101',
            'floor' => '1',
        ],
    ], 'location'],
    'invalid acquisition date' => [['acquisitionDate' => '2023-02-29'], 'acquisitionDate'],
    'negative acquisition value' => [['acquisitionValue' => -0.01], 'acquisitionValue'],
    'excessive acquisition precision' => [['acquisitionValue' => 10.123], 'acquisitionValue'],
    'invalid currency' => [['currency' => 'chf'], 'currency'],
]);

test('put requires a complete asset representation', function () {
    $token = assetApiToken(['assets:read', 'assets:write']);
    $asset = Asset::factory()->create();

    $this->withToken($token)
        ->putJson("/api/v1/assets/{$asset->id}", ['name' => 'Partial update'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'inventoryNumber',
            'category',
            'status',
            'location',
            'acquisitionDate',
            'acquisitionValue',
            'currency',
        ]);
});

test('duplicate inventory and serial numbers return field errors', function (string $field, string $errorKey) {
    $token = assetApiToken(['assets:write']);

    $this->withToken($token)
        ->postJson('/api/v1/assets', assetApiPayload())
        ->assertCreated();

    $duplicate = assetApiPayload([
        'inventoryNumber' => $field === 'inventoryNumber' ? 'AST-API-0001' : 'AST-API-0002',
        'serialNumber' => $field === 'serialNumber' ? 'SN-API-0001' : 'SN-API-0002',
    ]);

    $this->withToken($token)
        ->postJson('/api/v1/assets', $duplicate)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errorKey);
})->with([
    'inventory number' => ['inventoryNumber', 'inventoryNumber'],
    'serial number' => ['serialNumber', 'serialNumber'],
]);

test('invalid and unknown object ids return not found responses', function () {
    $token = assetApiToken(['assets:read']);

    $this->withToken($token)
        ->getJson('/api/v1/assets/not-an-object-id')
        ->assertNotFound();

    $this->withToken($token)
        ->getJson('/api/v1/assets/'.str_repeat('a', 24))
        ->assertNotFound();
});

test('asset api rate limit is enforced per token', function () {
    $token = assetApiToken(['assets:read']);

    foreach (range(1, 60) as $requestNumber) {
        $this->withToken($token)
            ->getJson('/api/v1/assets')
            ->assertOk("Request {$requestNumber} should be allowed.");
    }

    $this->withToken($token)
        ->getJson('/api/v1/assets')
        ->assertTooManyRequests();
});

/**
 * @param  list<string>  $abilities
 */
function assetApiToken(array $abilities = ['assets:read', 'assets:write']): string
{
    return User::factory()
        ->create()
        ->createToken('asset-api-test', $abilities)
        ->plainTextToken;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function assetApiPayload(array $overrides = []): array
{
    return array_replace([
        'inventoryNumber' => 'AST-API-0001',
        'serialNumber' => 'SN-API-0001',
        'name' => 'CNC Fräsmaschine',
        'category' => 'production',
        'status' => 'active',
        'location' => [
            'site' => 'Hauptsitz',
            'building' => 'A',
            'room' => '101',
        ],
        'acquisitionDate' => '2023-01-15',
        'acquisitionValue' => 125000.5,
        'currency' => 'CHF',
    ], $overrides);
}
