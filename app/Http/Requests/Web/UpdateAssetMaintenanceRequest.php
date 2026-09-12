<?php

namespace App\Http\Requests\Web;

use App\Models\Asset;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetMaintenanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $asset = $this->route('asset');

        return $asset instanceof Asset && ($this->user()?->can('update', $asset) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lastCompletedAt' => ['required', 'date_format:Y-m-d'],
            'intervalDays' => ['required', 'integer', 'min:1', 'max:3650'],
            'statusAfter' => [
                'required',
                'string',
                Rule::in([Asset::STATUS_ACTIVE, Asset::STATUS_MAINTENANCE]),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Calculate the follow-up date on the server so it cannot be tampered with by the browser.
     *
     * @return array{status: string, maintenance: array{last_completed_at: CarbonImmutable, next_due_at: CarbonImmutable, interval_days: int, note: string|null}}
     */
    public function assetAttributes(): array
    {
        $validated = $this->validated();
        $lastCompletedAt = CarbonImmutable::createFromFormat('!Y-m-d', $validated['lastCompletedAt']);
        $intervalDays = (int) $validated['intervalDays'];

        return [
            'status' => $validated['statusAfter'],
            'maintenance' => [
                'last_completed_at' => $lastCompletedAt,
                'next_due_at' => $lastCompletedAt->addDays($intervalDays),
                'interval_days' => $intervalDays,
                'note' => filled($validated['note'] ?? null) ? trim($validated['note']) : null,
            ],
        ];
    }
}
