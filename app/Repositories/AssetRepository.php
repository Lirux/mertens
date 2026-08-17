<?php

namespace App\Repositories;

use App\Models\Asset;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AssetRepository
{
    /**
     * @return LengthAwarePaginator<int, Asset>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator;

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

    public function delete(Asset $asset): bool;
}
