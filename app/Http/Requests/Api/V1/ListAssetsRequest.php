<?php

namespace App\Http\Requests\Api\V1;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListAssetsRequest extends FormRequest
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
            'updatedSince' => [
                'nullable',
                'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s.uP,Y-m-d\TH:i:s\Z,Y-m-d\TH:i:s.u\Z',
            ],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function updatedSince(): ?CarbonImmutable
    {
        $value = $this->validated('updatedSince');

        return is_string($value) ? CarbonImmutable::parse($value) : null;
    }

    public function limit(): int
    {
        return $this->integer('limit', 50);
    }

    public function offset(): int
    {
        return $this->integer('offset', 0);
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 400));
    }
}
