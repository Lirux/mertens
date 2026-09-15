<?php

namespace App\Repositories;

use App\Models\Asset;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Datenzugriffsvertrag für Web-App und API. Controller verwenden diesen Vertrag,
 * damit MongoDB-Abfragen und BSON-Datentypen in der Implementierung bleiben.
 */
interface AssetRepository
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
    ): LengthAwarePaginator;

    /**
     * @return list<string>
     */
    public function categories(): array;

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
    public function dashboardSummary(DateTimeInterface $today, int $upcomingLimit = 3): array;

    /**
     * @return Collection<int, Asset>
     */
    public function list(?DateTimeInterface $updatedSince = null, int $limit = 50, int $offset = 0): Collection;

    public function find(string $id): ?Asset;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Asset;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Asset $asset, array $attributes): Asset;

    /**
     * Atomically update the current maintenance snapshot and append its audit entry.
     *
     * @param  array{status: string, maintenance: array{last_completed_at: DateTimeInterface, next_due_at: DateTimeInterface, interval_days: int, note: string|null}}  $attributes
     */
    public function recordMaintenance(
        Asset $asset,
        array $attributes,
        string $recordedById,
        string $recordedByName,
    ): Asset;

    /**
     * @return list<array<string, mixed>>
     */
    public function maintenanceHistory(Asset $asset, ?int $limit = null): array;

    public function delete(Asset $asset): bool;
}
