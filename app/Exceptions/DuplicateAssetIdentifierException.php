<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class DuplicateAssetIdentifierException extends RuntimeException implements ShouldntReport
{
    public function __construct(
        public readonly string $field,
        ?Throwable $previous = null,
    ) {
        parent::__construct('An asset with this identifier already exists.', previous: $previous);
    }

    public function render(Request $request): JsonResponse
    {
        $apiField = $this->field === 'serial_number' ? 'serialNumber' : 'inventoryNumber';
        $message = $this->field === 'serial_number'
            ? 'The serial number has already been taken.'
            : 'The inventory number has already been taken.';

        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => [
                $apiField => [$message],
            ],
        ], 422);
    }
}
