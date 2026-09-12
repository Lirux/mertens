<?php

namespace App\Repositories;

use App\Exceptions\DuplicateAssetIdentifierException;
use App\Models\Asset;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\UpdateResult;
use RuntimeException;

class MongoAssetRepository implements AssetRepository
{
    /**
     * @return LengthAwarePaginator<int, Asset>
     */
    public function paginate(
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null,
        ?string $category = null,
        ?string $maintenanceDue = null,
    ): LengthAwarePaginator {
        $today = now()->startOfDay();

        return Asset::query()
            ->when($search, function ($query, string $search): void {
                $literalSearch = new Regex(preg_quote($search, '/'), 'i');

                $query->where(function ($query) use ($literalSearch): void {
                    $query->where('asset_number', 'regex', $literalSearch)
                        ->orWhere('name', 'regex', $literalSearch)
                        ->orWhere('serial_number', 'regex', $literalSearch);
                });
            })
            ->when($status, fn ($query, string $status) => $query->where('status', $status))
            ->when($category, fn ($query, string $category) => $query->where('category', $category))
            ->when(
                $maintenanceDue === 'overdue',
                fn ($query) => $query->where(
                    'maintenance.next_due_at',
                    '<',
                    $this->toUtcDateTime($today),
                ),
            )
            ->when(
                $maintenanceDue === 'next_30_days',
                fn ($query) => $query
                    ->where('maintenance.next_due_at', '>=', $this->toUtcDateTime($today))
                    ->where(
                        'maintenance.next_due_at',
                        '<=',
                        $this->toUtcDateTime($today->copy()->addDays(30)->endOfDay()),
                    ),
            )
            ->orderBy('asset_number')
            ->paginate(max(1, min(100, $perPage)));
    }

    /**
     * @return list<string>
     */
    public function categories(): array
    {
        $categories = Asset::query()
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category')
            ->filter(fn (mixed $category): bool => is_string($category) && $category !== '')
            ->unique()
            ->values()
            ->all();

        return array_values($categories);
    }

    /**
     * @return array{
     *     total: int,
     *     createdThisMonth: int,
     *     maintenanceDue: int,
     *     overdue: int,
     *     inactive: int,
     *     upcoming: Collection<int, Asset>
     * }
     */
    public function dashboardSummary(DateTimeInterface $today, int $upcomingLimit = 3): array
    {
        $day = Carbon::instance($today)->startOfDay();
        $nextThirtyDays = $day->copy()->addDays(30)->endOfDay();

        return [
            'total' => Asset::query()->count(),
            'createdThisMonth' => Asset::query()
                ->where('created_at', '>=', $this->toUtcDateTime($day->copy()->startOfMonth()))
                ->where('created_at', '<=', $this->toUtcDateTime($day->copy()->endOfMonth()))
                ->count(),
            'maintenanceDue' => Asset::query()
                ->where('maintenance.next_due_at', '<=', $this->toUtcDateTime($nextThirtyDays))
                ->count(),
            'overdue' => Asset::query()
                ->where('maintenance.next_due_at', '<', $this->toUtcDateTime($day))
                ->count(),
            'inactive' => Asset::query()->where('status', Asset::STATUS_INACTIVE)->count(),
            'upcoming' => Asset::query()
                ->whereNotNull('maintenance.next_due_at')
                ->orderBy('maintenance.next_due_at')
                ->limit(max(1, min(10, $upcomingLimit)))
                ->get(),
        ];
    }

    /**
     * @return Collection<int, Asset>
     */
    public function list(?DateTimeInterface $updatedSince = null, int $limit = 50, int $offset = 0): Collection
    {
        return Asset::query()
            ->when(
                $updatedSince,
                fn ($query, DateTimeInterface $date) => $query->where(
                    'updated_at',
                    '>=',
                    $this->toUtcDateTime($date),
                ),
            )
            ->orderBy('updated_at')
            ->orderBy('_id')
            ->skip(max(0, $offset))
            ->limit(max(1, min(100, $limit)))
            ->get();
    }

    public function find(string $id): ?Asset
    {
        if (mb_strlen($id) !== 24 || ctype_xdigit($id) === false) {
            return null;
        }

        return Asset::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Asset
    {
        $attributes = $this->normalizeAttributes($attributes);

        foreach (['serial_number', 'supplier', 'maintenance'] as $nullableField) {
            if (array_key_exists($nullableField, $attributes) && $attributes[$nullableField] === null) {
                unset($attributes[$nullableField]);
            }
        }

        try {
            return Asset::query()->create($attributes);
        } catch (BulkWriteException $exception) {
            $this->throwTranslatedWriteException($exception);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Asset $asset, array $attributes): Asset
    {
        $fieldsToRemove = collect(['serial_number', 'supplier', 'maintenance'])
            ->filter(fn (string $field): bool => array_key_exists($field, $attributes)
                && ($attributes[$field] === null || $attributes[$field] === ''))
            ->all();

        $attributes = $this->normalizeAttributes($attributes);

        foreach ($fieldsToRemove as $field) {
            $asset->unset($field);
            unset($attributes[$field]);
        }

        try {
            $asset->fill($attributes)->save();
        } catch (BulkWriteException $exception) {
            $this->throwTranslatedWriteException($exception);
        }

        return $asset->refresh();
    }

    /**
     * @param  array{status: string, maintenance: array{last_completed_at: DateTimeInterface, next_due_at: DateTimeInterface, interval_days: int, note: string|null}}  $attributes
     */
    public function recordMaintenance(
        Asset $asset,
        array $attributes,
        string $recordedById,
        string $recordedByName,
    ): Asset {
        $attributes = $this->normalizeAttributes($attributes);
        $recordedAt = new UTCDateTime(now());
        $maintenance = $attributes['maintenance'];

        $historyEntry = [
            '_id' => new ObjectId,
            'completed_at' => $maintenance['last_completed_at'],
            'next_due_at' => $maintenance['next_due_at'],
            'interval_days' => $maintenance['interval_days'],
            'status_after' => $attributes['status'],
            'note' => $maintenance['note'],
            'recorded_by' => [
                'id' => $recordedById,
                'name' => $recordedByName,
            ],
            'recorded_at' => $recordedAt,
        ];

        /** @var UpdateResult $result */
        $result = Asset::query()->raw(
            fn (MongoCollection $collection): UpdateResult => $collection->updateOne(
                ['_id' => new ObjectId((string) $asset->getKey())],
                [
                    '$set' => [
                        'status' => $attributes['status'],
                        'maintenance' => $maintenance,
                        'updated_at' => $recordedAt,
                    ],
                    '$push' => ['maintenance_history' => $historyEntry],
                ],
            ),
        );

        if ($result->getMatchedCount() !== 1) {
            throw new RuntimeException('The asset could not be updated with its maintenance record.');
        }

        return $asset->refresh();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function maintenanceHistory(Asset $asset, ?int $limit = null): array
    {
        $history = $asset->maintenance_history ?? [];

        usort($history, function (array $left, array $right): int {
            $completedComparison = $this->dateTimestamp($right['completed_at'] ?? null)
                <=> $this->dateTimestamp($left['completed_at'] ?? null);

            return $completedComparison !== 0
                ? $completedComparison
                : $this->dateTimestamp($right['recorded_at'] ?? null)
                    <=> $this->dateTimestamp($left['recorded_at'] ?? null);
        });

        return array_values($limit === null ? $history : array_slice($history, 0, max(0, $limit)));
    }

    public function delete(Asset $asset): bool
    {
        return (bool) $asset->delete();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $attributes): array
    {
        if (array_key_exists('serial_number', $attributes)) {
            $serialNumber = $attributes['serial_number'];

            $attributes['serial_number'] = is_string($serialNumber) && trim($serialNumber) !== ''
                ? trim($serialNumber)
                : null;
        }

        if (isset($attributes['maintenance']) && is_array($attributes['maintenance'])) {
            foreach (['last_completed_at', 'next_due_at'] as $dateField) {
                if (array_key_exists($dateField, $attributes['maintenance'])) {
                    $attributes['maintenance'][$dateField] = $this->toUtcDateTime(
                        $attributes['maintenance'][$dateField],
                    );
                }
            }
        }

        return $attributes;
    }

    private function toUtcDateTime(mixed $value): mixed
    {
        if ($value === null || $value instanceof UTCDateTime) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return new UTCDateTime($value);
        }

        if (is_string($value)) {
            return new UTCDateTime(Carbon::parse($value));
        }

        return $value;
    }

    private function dateTimestamp(mixed $value): int
    {
        if ($value instanceof UTCDateTime) {
            return $value->toDateTime()->getTimestamp();
        }

        return $value instanceof DateTimeInterface ? $value->getTimestamp() : 0;
    }

    private function throwTranslatedWriteException(BulkWriteException $exception): never
    {
        if (! str_contains($exception->getMessage(), 'E11000 duplicate key')) {
            throw $exception;
        }

        $field = str_contains($exception->getMessage(), 'assets_serial_number_unique')
            ? 'serial_number'
            : 'asset_number';

        throw new DuplicateAssetIdentifierException($field, $exception);
    }
}
