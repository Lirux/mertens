<?php

namespace App\Repositories;

use App\Exceptions\DuplicateAssetIdentifierException;
use App\Models\Asset;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\BulkWriteException;

class MongoAssetRepository implements AssetRepository
{
    /**
     * @return LengthAwarePaginator<int, Asset>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Asset::query()
            ->latest()
            ->paginate(max(1, min(100, $perPage)));
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

        if (array_key_exists('serial_number', $attributes) && $attributes['serial_number'] === null) {
            unset($attributes['serial_number']);
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
        $removeSerialNumber = array_key_exists('serial_number', $attributes)
            && (! is_string($attributes['serial_number']) || trim($attributes['serial_number']) === '');

        $attributes = $this->normalizeAttributes($attributes);

        if ($removeSerialNumber) {
            $asset->unset('serial_number');
            unset($attributes['serial_number']);
        }

        try {
            $asset->fill($attributes)->save();
        } catch (BulkWriteException $exception) {
            $this->throwTranslatedWriteException($exception);
        }

        return $asset->refresh();
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
