<?php

namespace App\Http\Resources\Web;

use App\Models\Asset;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use MongoDB\BSON\UTCDateTime;

/** @mixin Asset */
class AssetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->getKey(),
            'inventoryNumber' => $this->asset_number,
            'serialNumber' => $this->serial_number,
            'name' => $this->name,
            'category' => $this->category,
            'status' => $this->status,
            'location' => $this->location,
            'supplier' => $this->supplier === null ? null : [
                'externalId' => $this->supplier['external_id'],
                'name' => $this->supplier['name'],
            ],
            'acquisitionDate' => $this->acquired_at?->toDateString(),
            'acquisitionValue' => $this->acquisition_value,
            'currency' => $this->currency,
            'warrantyUntil' => $this->warranty_until?->toDateString(),
            'maintenance' => $this->maintenance === null ? null : [
                'lastCompletedAt' => $this->dateString($this->maintenance['last_completed_at']),
                'nextDueAt' => $this->dateString($this->maintenance['next_due_at']),
                'intervalDays' => $this->maintenance['interval_days'],
                'note' => $this->maintenance['note'] ?? null,
            ],
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }

    private function dateString(mixed $value): ?string
    {
        if ($value instanceof UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d');
        }

        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : null;
    }
}
