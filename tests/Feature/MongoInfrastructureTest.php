<?php

use App\Models\Asset;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\ObjectId;
use MongoDB\Laravel\Connection;
use MongoDB\Model\IndexInfo;

test('mongodb is the only active persistence connection', function () {
    expect(config('database.default'))->toBe('mongodb')
        ->and(array_keys(config('database.connections')))->toBe(['mongodb'])
        ->and(config('session.driver'))->toBe('mongodb')
        ->and(config('cache.default'))->toBe('mongodb')
        ->and(config('queue.default'))->toBe('mongodb')
        ->and(config('queue.batching.driver'))->toBe('mongodb')
        ->and(DB::connection())->toBeInstanceOf(Connection::class);
});

test('all required collections validators and indexes are provisioned', function () {
    $connection = DB::connection('mongodb');
    expect($connection)->toBeInstanceOf(Connection::class);

    /** @var Connection $connection */
    $database = $connection->getDatabase();
    $collections = iterator_to_array($database->listCollectionNames(), false);
    sort($collections);

    $expectedCollections = [
        'assets',
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'migrations',
        'password_reset_tokens',
        'sessions',
        'users',
    ];
    sort($expectedCollections);

    expect($collections)->toBe($expectedCollections);

    $assetCollectionInfo = iterator_to_array($database->listCollections([
        'filter' => ['name' => 'assets'],
    ]), false)[0];
    $userCollectionInfo = iterator_to_array($database->listCollections([
        'filter' => ['name' => 'users'],
    ]), false)[0];

    expect($assetCollectionInfo->getOptions())->toHaveKey('validator')
        ->and($userCollectionInfo->getOptions())->toHaveKey('validator');

    $assetSchema = $assetCollectionInfo->getOptions()['validator']['$jsonSchema'];

    expect($assetSchema['required'])->toContain('acquisition_value', 'currency')
        ->and($assetSchema['properties']['status']['enum'])->toBe([
            'active',
            'maintenance',
            'inactive',
            'retired',
        ])
        ->and($assetSchema['properties']['acquisition_value']['bsonType'])->toBe('decimal')
        ->and($assetSchema['properties']['currency']['pattern'])->toBe('^[A-Z]{3}$');

    $assetIndexes = collect(iterator_to_array($database->assets->listIndexes()))
        ->keyBy(fn (IndexInfo $index): string => $index->getName());

    expect($assetIndexes->get('assets_asset_number_unique')?->isUnique())->toBeTrue()
        ->and($assetIndexes->get('assets_serial_number_unique')?->isUnique())->toBeTrue()
        ->and($assetIndexes->get('assets_serial_number_unique')?->isSparse())->toBeTrue()
        ->and($assetIndexes)->toHaveKey('status_1_maintenance.next_due_at_1');

    $uniqueIndexes = [
        'users' => 'users_email_unique',
        'password_reset_tokens' => 'password_reset_tokens_email_unique',
        'failed_jobs' => 'failed_jobs_uuid_unique',
    ];

    foreach ($uniqueIndexes as $collectionName => $indexName) {
        $index = collect(iterator_to_array($database->{$collectionName}->listIndexes()))
            ->first(fn (IndexInfo $index): bool => $index->getName() === $indexName);

        expect($index)->toBeInstanceOf(IndexInfo::class)
            ->and($index->isUnique())->toBeTrue();
    }

    $jobIndexes = collect(iterator_to_array($database->jobs->listIndexes()))
        ->keyBy(fn (IndexInfo $index): string => $index->getName());

    expect($jobIndexes)->toHaveKeys([
        'queue_1',
        'queue_1_reserved_1_available_at_1',
        'queue_1_reserved_at_1',
    ]);

    foreach (['sessions', 'cache', 'cache_locks'] as $collectionName) {
        $indexes = iterator_to_array($database->{$collectionName}->listIndexes());

        expect(collect($indexes)->contains(
            fn (IndexInfo $index): bool => $index->getName() === 'expires_at_1' && $index->isTtl(),
        ))->toBeTrue();
    }
});

test('cache locks and queued jobs use mongodb', function () {
    Cache::put('asset-count', 4, 60);
    expect(Cache::get('asset-count'))->toBe(4);

    $lock = Cache::lock('asset-import', 30);
    expect($lock->get())->toBeTrue();

    $connection = DB::connection('mongodb');
    expect($connection)->toBeInstanceOf(Connection::class);

    /** @var Connection $connection */
    $database = $connection->getDatabase();

    expect($database->cache->countDocuments())->toBe(1)
        ->and($database->cache_locks->countDocuments())->toBe(1);

    $lock->release();

    $queue = Queue::connection('mongodb');
    $jobId = $queue->pushRaw(json_encode([
        'uuid' => 'test-job',
        'displayName' => 'MongoDB infrastructure test',
        'job' => 'test',
        'data' => [],
    ], JSON_THROW_ON_ERROR));

    expect($jobId)->toBeInstanceOf(ObjectId::class)
        ->and($database->jobs->countDocuments())->toBe(1);

    $job = $queue->pop();
    expect($job)->not->toBeNull();

    $job?->delete();
    expect($database->jobs->countDocuments())->toBe(0);
});

test('fortify authentication persists users and sessions in mongodb', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(config('fortify.home'));

    $this->assertAuthenticated();

    $connection = DB::connection('mongodb');
    expect($connection)->toBeInstanceOf(Connection::class);

    /** @var Connection $connection */
    expect($connection->getDatabase()->sessions->countDocuments([
        'user_id' => $user->id,
    ]))->toBe(1);
});

test('the database seeder creates reproducible users and assets', function () {
    $this->seed(DatabaseSeeder::class);

    $assets = Asset::query()->get();

    expect(User::query()->where('email', 'test@example.com')->count())->toBe(1)
        ->and($assets->pluck('asset_number')->all())->toEqualCanonicalizing([
            'AST-00001',
            'AST-00002',
            'AST-00003',
            'AST-00004',
        ])
        ->and($assets->pluck('currency')->unique()->all())->toBe(['CHF'])
        ->and($assets->every(
            fn (Asset $asset): bool => $asset->getRawOriginal('acquisition_value') instanceof Decimal128,
        ))->toBeTrue()
        ->and($assets->firstWhere('asset_number', 'AST-00004')?->status)->toBe(Asset::STATUS_INACTIVE);
});
