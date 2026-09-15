<?php

namespace App\Http\Requests\Web;

use App\Models\Asset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AssetWriteRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inventoryNumber' => [
                'required',
                'string',
                'max:50',
                Rule::unique(Asset::class, 'asset_number')->ignore($this->existingAsset()),
            ],
            'serialNumber' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique(Asset::class, 'serial_number')->ignore($this->existingAsset()),
            ],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(Asset::STATUSES)],
            'location' => ['required', 'array:site,building,room'],
            'location.site' => ['required', 'string', 'max:150'],
            'location.building' => ['required', 'string', 'max:100'],
            'location.room' => ['required', 'string', 'max:100'],
            'supplier' => ['nullable', 'array:externalId,name'],
            'supplier.externalId' => ['nullable', 'string', 'max:100', 'required_with:supplier.name'],
            'supplier.name' => ['nullable', 'string', 'max:150', 'required_with:supplier.externalId'],
            'acquisitionDate' => ['nullable', 'date_format:Y-m-d'],
            'acquisitionValue' => ['required', 'decimal:0,2', 'min:0'],
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
            'warrantyUntil' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:acquisitionDate',
            ],
            'maintenance' => ['nullable', 'array:nextDueAt,intervalDays'],
            'maintenance.nextDueAt' => [
                'nullable',
                'date_format:Y-m-d',
                'required_with:maintenance.intervalDays',
            ],
            'maintenance.intervalDays' => [
                'nullable',
                'integer',
                'min:1',
                'max:3650',
                'required_with:maintenance.nextDueAt',
            ],
        ];
    }

    /**
     * Übersetzt die Formularfelder in MongoDB-Attribute. Leere optionale Gruppen
     * werden entfernt; Stammdatenänderungen schreiben die Wartungshistorie nicht um.
     *
     * @return array<string, mixed>
     */
    public function assetAttributes(): array
    {
        $validated = $this->validated();
        $supplier = $validated['supplier'] ?? [];
        $maintenance = $validated['maintenance'] ?? [];
        $existingMaintenance = $this->existingAsset()?->maintenance;

        return [
            'asset_number' => $validated['inventoryNumber'],
            'serial_number' => $validated['serialNumber'] ?? null,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'status' => $validated['status'],
            'location' => $validated['location'],
            'supplier' => $this->supplierAttributes($supplier),
            'acquired_at' => $validated['acquisitionDate'] ?? null,
            'acquisition_value' => $validated['acquisitionValue'],
            'currency' => $validated['currency'],
            'warranty_until' => $validated['warrantyUntil'] ?? null,
            'maintenance' => $this->maintenanceAttributes($maintenance, $existingMaintenance),
        ];
    }

    protected function existingAsset(): ?Asset
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $supplier
     * @return array{external_id: string, name: string}|null
     */
    private function supplierAttributes(array $supplier): ?array
    {
        if (blank($supplier['externalId'] ?? null) && blank($supplier['name'] ?? null)) {
            return null;
        }

        return [
            'external_id' => $supplier['externalId'],
            'name' => $supplier['name'],
        ];
    }

    /**
     * @param  array<string, mixed>  $maintenance
     * @param  array<string, mixed>|null  $existingMaintenance
     * @return array{last_completed_at: mixed, next_due_at: string, interval_days: int, note?: mixed}|null
     */
    private function maintenanceAttributes(array $maintenance, ?array $existingMaintenance): ?array
    {
        if (blank($maintenance['nextDueAt'] ?? null) && blank($maintenance['intervalDays'] ?? null)) {
            return null;
        }

        return array_filter([
            'last_completed_at' => $existingMaintenance['last_completed_at'] ?? null,
            'next_due_at' => $maintenance['nextDueAt'],
            'interval_days' => (int) $maintenance['intervalDays'],
            'note' => $existingMaintenance['note'] ?? null,
        ], fn (mixed $value, string $key): bool => $key !== 'note' || $value !== null, ARRAY_FILTER_USE_BOTH);
    }
}
