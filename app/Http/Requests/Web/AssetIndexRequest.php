<?php

namespace App\Http\Requests\Web;

use App\Models\Asset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Asset::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', Rule::in(Asset::STATUSES)],
            'category' => ['nullable', 'string', 'max:100'],
            'maintenanceDue' => ['nullable', 'string', Rule::in(['overdue', 'next_30_days'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{search: string|null, status: string|null, category: string|null, maintenanceDue: string|null}
     */
    public function filters(): array
    {
        return [
            'search' => $this->normalizedString('search'),
            'status' => $this->normalizedString('status'),
            'category' => $this->normalizedString('category'),
            'maintenanceDue' => $this->normalizedString('maintenanceDue'),
        ];
    }

    private function normalizedString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
