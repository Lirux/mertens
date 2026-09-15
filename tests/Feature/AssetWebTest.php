<?php

use App\Models\Asset;
use App\Models\User;
use App\Repositories\AssetRepository;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('asset pages require a verified session', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->assetManager()->unverified()->create())
        ->get(route($routeName))
        ->assertRedirect(route('verification.notice'));
})->with(['dashboard', 'assets.index', 'assets.create']);

test('the reproducible demo data includes a verified user and assets', function () {
    $this->seed();

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeTrue()
        ->and(Asset::query()->count())->toBe(4)
        ->and(Asset::query()->get()->every(
            fn (Asset $asset): bool => count($asset->maintenance_history ?? []) === 2,
        ))->toBeTrue();

    $asset = Asset::query()->where('asset_number', 'AST-00001')->firstOrFail();
    $history = app(AssetRepository::class)->maintenanceHistory($asset);

    expect($history[0]['recorded_by'])->toBe([
        'id' => $user->id,
        'name' => $user->name,
    ])
        ->and($history[0]['note'])->toBe('Führungen geschmiert und Werkzeugwechsler geprüft.');
});

test('dashboard shows calculated asset and maintenance figures', function () {
    Carbon::setTestNow('2026-08-17 10:00:00');
    $user = User::factory()->assetManager()->create();

    createWebAsset([
        'asset_number' => 'AST-00001',
        'created_at' => new UTCDateTime(Carbon::parse('2026-08-01')),
        'maintenance.next_due_at' => new UTCDateTime(Carbon::parse('2026-08-10')),
    ]);
    createWebAsset([
        'asset_number' => 'AST-00002',
        'created_at' => new UTCDateTime(Carbon::parse('2026-08-05')),
        'maintenance.next_due_at' => new UTCDateTime(Carbon::parse('2026-08-20')),
    ]);
    createWebAsset([
        'asset_number' => 'AST-00003',
        'status' => Asset::STATUS_INACTIVE,
        'created_at' => new UTCDateTime(Carbon::parse('2026-07-15')),
        'maintenance.next_due_at' => new UTCDateTime(Carbon::parse('2026-09-10')),
    ]);
    createWebAsset([
        'asset_number' => 'AST-00004',
        'created_at' => new UTCDateTime(Carbon::parse('2026-06-01')),
        'maintenance.next_due_at' => new UTCDateTime(Carbon::parse('2026-10-20')),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats', [
                'total' => 4,
                'createdThisMonth' => 2,
                'maintenanceDue' => 3,
                'overdue' => 1,
                'inactive' => 1,
            ])
            ->has('upcomingMaintenance', 3)
            ->where('upcomingMaintenance.0.inventoryNumber', 'AST-00001')
            ->where('upcomingMaintenance.1.inventoryNumber', 'AST-00002')
            ->where('upcomingMaintenance.2.inventoryNumber', 'AST-00003'),
        );
});

test('asset index provides sorted pagination and categories', function () {
    $user = User::factory()->assetManager()->create();

    foreach (range(21, 1) as $number) {
        createWebAsset([
            'asset_number' => sprintf('AST-%05d', $number),
            'category' => $number % 2 === 0 ? 'production' : 'logistics',
        ]);
    }

    $this->actingAs($user)
        ->get(route('assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('assets/index')
            ->where('assets.total', 21)
            ->where('assets.per_page', 20)
            ->where('assets.current_page', 1)
            ->has('assets.data', 20)
            ->where('assets.data.0.inventoryNumber', 'AST-00001')
            ->where('assets.data.19.inventoryNumber', 'AST-00020')
            ->where('categories', ['logistics', 'production'])
            ->where('filters', [
                'search' => null,
                'status' => null,
                'category' => null,
                'maintenanceDue' => null,
            ]),
        );

    $this->actingAs($user)
        ->get(route('assets.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('assets.current_page', 2)
            ->has('assets.data', 1)
            ->where('assets.data.0.inventoryNumber', 'AST-00021'),
        );
});

test('asset filters combine literal search status category and maintenance date', function () {
    Carbon::setTestNow('2026-08-17 10:00:00');
    $user = User::factory()->assetManager()->create();

    createWebAsset([
        'asset_number' => 'AST-[42]',
        'name' => 'Hydraulikaggregat',
        'category' => 'production',
        'status' => Asset::STATUS_ACTIVE,
        'maintenance.next_due_at' => new UTCDateTime(Carbon::parse('2026-08-25')),
    ]);
    createWebAsset([
        'asset_number' => 'AST-00420',
        'name' => 'Hydraulikpumpe',
        'category' => 'production',
        'status' => Asset::STATUS_MAINTENANCE,
        'maintenance.next_due_at' => new UTCDateTime(Carbon::parse('2026-08-01')),
    ]);
    createWebAsset([
        'asset_number' => 'AST-00003',
        'name' => 'Hydraulikwerkzeug',
        'category' => 'logistics',
        'status' => Asset::STATUS_ACTIVE,
        'maintenance.next_due_at' => new UTCDateTime(Carbon::parse('2026-08-26')),
    ]);

    $filters = [
        'search' => '[42]',
        'status' => Asset::STATUS_ACTIVE,
        'category' => 'production',
        'maintenanceDue' => 'next_30_days',
    ];

    $this->actingAs($user)
        ->get(route('assets.index', $filters))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', $filters)
            ->where('assets.total', 1)
            ->has('assets.data', 1)
            ->where('assets.data.0.inventoryNumber', 'AST-[42]'),
        );

    $this->actingAs($user)
        ->get(route('assets.index', ['maintenanceDue' => 'overdue']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('assets.total', 1)
            ->where('assets.data.0.inventoryNumber', 'AST-00420'),
        );
});

test('asset index has a complete empty result contract', function () {
    $this->actingAs(User::factory()->assetManager()->create())
        ->get(route('assets.index', ['search' => 'not-found']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('assets/index')
            ->where('assets.total', 0)
            ->has('assets.data', 0)
            ->where('filters.search', 'not-found'),
        );
});

test('asset create edit detail and maintenance pages expose their inertia contracts', function () {
    $user = User::factory()->assetManager()->create();
    $asset = createWebAsset([
        'asset_number' => 'AST-00433',
        'name' => 'Prüfstand 4.3.3',
        'category' => 'measurement',
    ]);

    $this->actingAs($user)
        ->get(route('assets.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('assets/create')
            ->where('categories', ['measurement']),
        );

    $this->actingAs($user)
        ->get(route('assets.show', $asset))
        ->assertInertia(fn (Assert $page) => $page
            ->component('assets/show')
            ->where('asset.inventoryNumber', 'AST-00433')
            ->where('asset.name', 'Prüfstand 4.3.3')
            ->has('asset.location')
            ->has('asset.maintenance')
            ->has('maintenanceHistory', 0),
        );

    $this->actingAs($user)
        ->get(route('assets.edit', $asset))
        ->assertInertia(fn (Assert $page) => $page
            ->component('assets/edit')
            ->where('asset.id', $asset->id)
            ->where('categories', ['measurement']),
        );

    $this->actingAs($user)
        ->get(route('assets.maintenance.edit', $asset))
        ->assertInertia(fn (Assert $page) => $page
            ->component('assets/maintenance')
            ->where('asset.id', $asset->id)
            ->has('maintenanceHistory', 0),
        );
});

test('a verified user can create an asset with all supported fields', function () {
    $response = $this->actingAs(User::factory()->assetManager()->create())
        ->post(route('assets.store'), validWebAssetPayload());

    $asset = Asset::query()->where('asset_number', 'AST-90001')->firstOrFail();

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('assets.show', $asset))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Asset wurde erfolgreich erstellt.',
        ]);

    expect($asset->serial_number)->toBe('SN-WEB-90001')
        ->and($asset->acquisition_value)->toBe('125000.00')
        ->and($asset->currency)->toBe('CHF')
        ->and($asset->location)->toBe([
            'site' => 'Zürich',
            'building' => 'Werkhalle 2',
            'room' => 'Prüfstand',
        ])
        ->and($asset->supplier)->toBe([
            'external_id' => 'SUP-9001',
            'name' => 'Muster Technik AG',
        ])
        ->and($asset->maintenance['last_completed_at'])->toBeNull()
        ->and($asset->maintenance['next_due_at'])->toBeInstanceOf(DateTimeInterface::class)
        ->and($asset->maintenance['interval_days'])->toBe(180);
});

test('optional serial supplier and maintenance fields can be omitted', function () {
    $payload = validWebAssetPayload([
        'serialNumber' => '',
        'supplier' => ['externalId' => '', 'name' => ''],
        'maintenance' => ['nextDueAt' => '', 'intervalDays' => ''],
    ]);

    $this->actingAs(User::factory()->assetManager()->create())
        ->post(route('assets.store'), $payload)
        ->assertSessionHasNoErrors();

    $asset = Asset::query()->where('asset_number', 'AST-90001')->firstOrFail();

    expect($asset->getAttributes())->not->toHaveKeys([
        'serial_number',
        'supplier',
        'maintenance',
    ]);
});

test('asset form validates nested and dependent fields', function () {
    $payload = validWebAssetPayload([
        'currency' => 'chf',
        'supplier' => ['externalId' => '', 'name' => 'Muster Technik AG'],
        'acquisitionDate' => '2026-02-01',
        'warrantyUntil' => '2026-01-31',
        'maintenance' => ['nextDueAt' => '', 'intervalDays' => 180],
    ]);

    $this->actingAs(User::factory()->assetManager()->create())
        ->from(route('assets.create'))
        ->post(route('assets.store'), $payload)
        ->assertRedirect(route('assets.create'))
        ->assertSessionHasErrors([
            'currency',
            'supplier.externalId',
            'warrantyUntil',
            'maintenance.nextDueAt',
        ]);

    expect(Asset::query()->count())->toBe(0);
});

test('asset number and serial number must remain unique', function () {
    createWebAsset([
        'asset_number' => 'AST-90001',
        'serial_number' => 'SN-WEB-90001',
    ]);

    $this->actingAs(User::factory()->assetManager()->create())
        ->post(route('assets.store'), validWebAssetPayload())
        ->assertSessionHasErrors(['inventoryNumber', 'serialNumber']);

    expect(Asset::query()->count())->toBe(1);
});

test('a verified user can update an asset without colliding with its own identifiers', function () {
    $asset = createWebAsset([
        'asset_number' => 'AST-90001',
        'serial_number' => 'SN-WEB-90001',
        'maintenance.note' => 'Existing note',
    ]);
    $payload = validWebAssetPayload([
        'name' => 'Hydraulikaggregat HPU-500',
        'status' => Asset::STATUS_MAINTENANCE,
        'location' => [
            'site' => 'Bern',
            'building' => 'B',
            'room' => '204',
        ],
    ]);

    $response = $this->actingAs(User::factory()->assetManager()->create())
        ->put(route('assets.update', $asset), $payload);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('assets.show', $asset))
        ->assertInertiaFlash('toast.message', 'Änderungen wurden gespeichert.');

    $asset->refresh();

    expect($asset->name)->toBe('Hydraulikaggregat HPU-500')
        ->and($asset->status)->toBe(Asset::STATUS_MAINTENANCE)
        ->and($asset->location['site'])->toBe('Bern')
        ->and($asset->maintenance['note'])->toBe('Existing note');
});

test('maintenance completion calculates the next date and updates status and note', function () {
    Carbon::setTestNow('2026-08-17 10:00:00');
    $asset = createWebAsset(['status' => Asset::STATUS_MAINTENANCE]);
    $user = User::factory()->assetManager()->create(['name' => 'Nina Beispiel']);

    $response = $this->actingAs($user)
        ->put(route('assets.maintenance.update', $asset), [
            'lastCompletedAt' => '2026-08-17',
            'intervalDays' => 90,
            'statusAfter' => Asset::STATUS_ACTIVE,
            'note' => '  Filter und Dichtung ersetzt.  ',
            'nextDueAt' => '2099-01-01',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('assets.show', $asset))
        ->assertInertiaFlash(
            'toast.message',
            'Wartung wurde erfasst und der Folgetermin aktualisiert.',
        );

    $asset->refresh();
    $maintenance = $asset->maintenance;
    $history = $asset->maintenance_history;

    expect($asset->status)->toBe(Asset::STATUS_ACTIVE)
        ->and($maintenance['last_completed_at']->format('Y-m-d'))->toBe('2026-08-17')
        ->and($maintenance['next_due_at']->format('Y-m-d'))->toBe('2026-11-15')
        ->and($maintenance['interval_days'])->toBe(90)
        ->and($maintenance['note'])->toBe('Filter und Dichtung ersetzt.')
        ->and($history)->toHaveCount(1)
        ->and($history[0]['id'])->toBeInstanceOf(ObjectId::class)
        ->and($history[0]['completed_at']->format('Y-m-d'))->toBe('2026-08-17')
        ->and($history[0]['next_due_at']->format('Y-m-d'))->toBe('2026-11-15')
        ->and($history[0]['interval_days'])->toBe(90)
        ->and($history[0]['status_after'])->toBe(Asset::STATUS_ACTIVE)
        ->and($history[0]['note'])->toBe('Filter und Dichtung ersetzt.')
        ->and($history[0]['recorded_by'])->toBe([
            'id' => $user->id,
            'name' => 'Nina Beispiel',
        ])
        ->and($history[0]['recorded_at']->format('Y-m-d H:i:s'))
        ->toBe('2026-08-17 10:00:00');
});

test('maintenance may keep an asset in maintenance and validates its limits', function () {
    $asset = createWebAsset();
    $user = User::factory()->assetManager()->create();

    $this->actingAs($user)
        ->put(route('assets.maintenance.update', $asset), [
            'lastCompletedAt' => '2026-08-17',
            'intervalDays' => 365,
            'statusAfter' => Asset::STATUS_MAINTENANCE,
            'note' => null,
        ])
        ->assertSessionHasNoErrors();

    expect($asset->refresh()->status)->toBe(Asset::STATUS_MAINTENANCE)
        ->and($asset->maintenance_history)->toHaveCount(1)
        ->and($asset->maintenance_history[0]['note'])->toBeNull();

    $this->actingAs($user)
        ->put(route('assets.maintenance.update', $asset), [
            'lastCompletedAt' => 'not-a-date',
            'intervalDays' => 0,
            'statusAfter' => Asset::STATUS_RETIRED,
            'note' => str_repeat('x', 1001),
        ])
        ->assertSessionHasErrors([
            'lastCompletedAt',
            'intervalDays',
            'statusAfter',
            'note',
        ]);

    expect($asset->refresh()->maintenance_history)->toHaveCount(1);
});

test('maintenance history is sorted newest first and limited on the maintenance form', function () {
    Carbon::setTestNow('2026-08-17 10:00:00');
    $asset = createWebAsset();
    $user = User::factory()->assetManager()->create();

    foreach (['2026-05-01', '2026-08-01', '2026-06-01', '2026-07-01'] as $completedAt) {
        $this->actingAs($user)
            ->put(route('assets.maintenance.update', $asset), [
                'lastCompletedAt' => $completedAt,
                'intervalDays' => 90,
                'statusAfter' => Asset::STATUS_ACTIVE,
                'note' => 'Wartung vom '.$completedAt,
            ])
            ->assertSessionHasNoErrors();
    }

    $this->actingAs($user)
        ->get(route('assets.show', $asset))
        ->assertInertia(fn (Assert $page) => $page
            ->has('maintenanceHistory', 4)
            ->where('maintenanceHistory.0.completedAt', '2026-08-01')
            ->where('maintenanceHistory.1.completedAt', '2026-07-01')
            ->where('maintenanceHistory.2.completedAt', '2026-06-01')
            ->where('maintenanceHistory.3.completedAt', '2026-05-01'),
        );

    $this->actingAs($user)
        ->get(route('assets.maintenance.edit', $asset))
        ->assertInertia(fn (Assert $page) => $page
            ->has('maintenanceHistory', 3)
            ->where('maintenanceHistory.0.completedAt', '2026-08-01')
            ->where('maintenanceHistory.1.completedAt', '2026-07-01')
            ->where('maintenanceHistory.2.completedAt', '2026-06-01'),
        );
});

test('editing asset master data does not rewrite maintenance history', function () {
    $asset = createWebAsset([
        'asset_number' => 'AST-90001',
        'serial_number' => 'SN-WEB-90001',
    ]);
    $user = User::factory()->assetManager()->create();

    $this->actingAs($user)
        ->put(route('assets.maintenance.update', $asset), [
            'lastCompletedAt' => '2026-08-17',
            'intervalDays' => 90,
            'statusAfter' => Asset::STATUS_ACTIVE,
            'note' => 'Historischer Eintrag',
        ])
        ->assertSessionHasNoErrors();

    $historyBeforeUpdate = $asset->refresh()->maintenance_history;

    $this->actingAs($user)
        ->put(route('assets.update', $asset), validWebAssetPayload([
            'name' => 'Aktualisierte Stammdaten',
        ]))
        ->assertSessionHasNoErrors();

    expect($asset->refresh()->maintenance_history)->toEqual($historyBeforeUpdate);
});

test('a verified user can delete an asset and receives confirmation', function () {
    $asset = createWebAsset();

    $response = $this->actingAs(User::factory()->assetManager()->create())
        ->delete(route('assets.destroy', $asset));

    $response
        ->assertRedirect(route('assets.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Asset wurde gelöscht.',
        ]);
    $this->assertModelMissing($asset);
});

test('unknown and malformed asset identifiers return not found', function (string $identifier) {
    $this->actingAs(User::factory()->assetManager()->create())
        ->get('/assets/'.$identifier)
        ->assertNotFound();
})->with([str_repeat('a', 24), 'not-an-object-id']);

/**
 * @param  array<string, mixed>  $overrides
 */
function createWebAsset(array $overrides = []): Asset
{
    $attributes = [
        'asset_number' => 'AST-'.fake()->unique()->numerify('#####'),
        'name' => 'Hydraulikaggregat HPU-400',
        'category' => 'production',
        'status' => Asset::STATUS_ACTIVE,
        'serial_number' => 'SN-'.fake()->unique()->bothify('WEB-#####'),
        'maintenance' => [
            'last_completed_at' => new UTCDateTime(Carbon::parse('2026-02-01')),
            'next_due_at' => new UTCDateTime(Carbon::parse('2027-02-01')),
            'interval_days' => 365,
        ],
    ];

    foreach ($overrides as $key => $value) {
        data_set($attributes, $key, $value);
    }

    return Asset::factory()->create($attributes);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validWebAssetPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'inventoryNumber' => 'AST-90001',
        'serialNumber' => 'SN-WEB-90001',
        'name' => 'Hydraulikaggregat HPU-400',
        'category' => 'production',
        'status' => Asset::STATUS_ACTIVE,
        'location' => [
            'site' => 'Zürich',
            'building' => 'Werkhalle 2',
            'room' => 'Prüfstand',
        ],
        'supplier' => [
            'externalId' => 'SUP-9001',
            'name' => 'Muster Technik AG',
        ],
        'acquisitionDate' => '2026-01-15',
        'acquisitionValue' => '125000.00',
        'currency' => 'CHF',
        'warrantyUntil' => '2028-01-15',
        'maintenance' => [
            'nextDueAt' => '2026-12-01',
            'intervalDays' => 180,
        ],
    ], $overrides);
}
