<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Asset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class AssetWriteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
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
            'acquisitionDate' => ['required', 'date_format:Y-m-d'],
            'acquisitionValue' => ['required', 'decimal:0,2', 'min:0'],
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assetAttributes(): array
    {
        $validated = $this->validated();

        return [
            'asset_number' => $validated['inventoryNumber'],
            'serial_number' => $validated['serialNumber'] ?? null,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'status' => $validated['status'],
            'location' => $validated['location'],
            'acquired_at' => $validated['acquisitionDate'],
            'acquisition_value' => $validated['acquisitionValue'],
            'currency' => $validated['currency'],
        ];
    }

    protected function existingAsset(): ?Asset
    {
        return null;
    }
}
