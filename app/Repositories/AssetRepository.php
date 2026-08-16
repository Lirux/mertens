<?php

namespace App\Repositories;

use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AssetRepository
{
    /**
     * @return LengthAwarePaginator<int, Asset>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator;

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
