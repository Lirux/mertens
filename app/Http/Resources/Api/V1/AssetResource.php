<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'acquisitionDate' => $this->acquired_at?->toDateString(),
            'acquisitionValue' => (float) $this->acquisition_value,
            'currency' => $this->currency,
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
