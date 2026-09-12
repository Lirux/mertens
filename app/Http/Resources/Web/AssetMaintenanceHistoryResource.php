<?php

namespace App\Http\Resources\Web;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use MongoDB\BSON\UTCDateTime;

class AssetMaintenanceHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $entry */
        $entry = $this->resource;
        $recordedBy = is_array($entry['recorded_by'] ?? null)
            ? $entry['recorded_by']
            : [];

        return [
            'id' => $this->identifier($entry['id'] ?? $entry['_id'] ?? null),
            'completedAt' => $this->dateString($entry['completed_at'] ?? null),
            'nextDueAt' => $this->dateString($entry['next_due_at'] ?? null),
            'intervalDays' => (int) ($entry['interval_days'] ?? 0),
            'statusAfter' => (string) ($entry['status_after'] ?? ''),
            'note' => isset($entry['note']) && is_string($entry['note']) ? $entry['note'] : null,
            'recordedBy' => [
                'id' => isset($recordedBy['id']) ? (string) $recordedBy['id'] : null,
                'name' => (string) ($recordedBy['name'] ?? ''),
            ],
            'recordedAt' => $this->dateTimeString($entry['recorded_at'] ?? null),
        ];
    }

    private function identifier(mixed $value): string
    {
        return (string) $value;
    }

    private function dateString(mixed $value): ?string
    {
        if ($value instanceof UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d');
        }

        return $value instanceof DateTimeInterface ? $value->format('Y-m-d') : null;
    }

    private function dateTimeString(mixed $value): ?string
    {
        if ($value instanceof UTCDateTime) {
            return $value->toDateTime()->format(DATE_ATOM);
        }

        return $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : null;
    }
}
