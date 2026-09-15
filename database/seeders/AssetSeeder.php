<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Laravel\Connection;
use RuntimeException;

class AssetSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->where('email', 'test@example.com')->first();
        $recordedBy = [
            'id' => $user?->id,
            'name' => $user->name ?? 'Demo-Daten',
        ];

        foreach ($this->assets($recordedBy) as $attributes) {
            $maintenanceHistory = $attributes['maintenance_history'];
            unset($attributes['maintenance_history']);

            $asset = Asset::query()->updateOrCreate(
                ['asset_number' => $attributes['asset_number']],
                $attributes,
            );

            $connection = $asset->getConnection();

            if (! $connection instanceof Connection) {
                throw new RuntimeException('Asset demo data requires the MongoDB connection.');
            }

            $connection->getCollection($asset->getTable())->updateOne(
                ['_id' => new ObjectId((string) $asset->getKey())],
                ['$set' => ['maintenance_history' => $maintenanceHistory]],
            );
        }
    }

    /**
     * @param  array{id: string|null, name: string}  $recordedBy
     * @return list<array<string, mixed>>
     */
    private function assets(array $recordedBy): array
    {
        return [
            $this->asset(
                assetNumber: 'AST-00001',
                name: 'CNC Fräsmaschine DMU 50',
                category: 'production',
                status: Asset::STATUS_ACTIVE,
                acquisitionValue: '185000.00',
                serialNumber: 'SN-DMU50-001',
                building: 'A',
                room: '101',
                supplierId: 'SUP-0001',
                supplierName: 'DMG MORI Schweiz AG',
                lastMaintenance: '2026-02-15',
                nextMaintenance: '2027-02-15',
                maintenanceNote: 'Führungen geschmiert und Werkzeugwechsler geprüft.',
                recordedBy: $recordedBy,
            ),
            $this->asset(
                assetNumber: 'AST-00002',
                name: 'Schraubenkompressor GA 22',
                category: 'production',
                status: Asset::STATUS_MAINTENANCE,
                acquisitionValue: '12500.00',
                serialNumber: 'SN-GA22-014',
                building: 'A',
                room: '108',
                supplierId: 'SUP-0002',
                supplierName: 'Atlas Copco (Schweiz) AG',
                lastMaintenance: '2025-08-20',
                nextMaintenance: '2026-08-20',
                maintenanceNote: 'Filter ersetzt; Druckverlust wird weiter beobachtet.',
                recordedBy: $recordedBy,
            ),
            $this->asset(
                assetNumber: 'AST-00003',
                name: 'Elektro-Gabelstapler E20',
                category: 'logistics',
                status: Asset::STATUS_ACTIVE,
                acquisitionValue: '45000.00',
                serialNumber: 'SN-E20-207',
                building: 'B',
                room: 'Lager',
                supplierId: 'SUP-0003',
                supplierName: 'Linde Material Handling Schweiz AG',
                lastMaintenance: '2026-04-10',
                nextMaintenance: '2027-04-10',
                maintenanceNote: 'Batterie, Bremsen und Hubmast geprüft.',
                recordedBy: $recordedBy,
            ),
            $this->asset(
                assetNumber: 'AST-00004',
                name: 'Koordinatenmessgerät CRYSTA-Apex',
                category: 'measurement',
                status: Asset::STATUS_INACTIVE,
                acquisitionValue: '8000.00',
                serialNumber: 'SN-CRYSTA-032',
                building: 'C',
                room: '205',
                supplierId: 'SUP-0004',
                supplierName: 'Mitutoyo (Schweiz) AG',
                lastMaintenance: '2025-11-01',
                nextMaintenance: '2026-11-01',
                maintenanceNote: 'Kalibrierung abgeschlossen und Messprotokoll geprüft.',
                recordedBy: $recordedBy,
            ),
        ];
    }

    /**
     * @param  array{id: string|null, name: string}  $recordedBy
     * @return array<string, mixed>
     */
    private function asset(
        string $assetNumber,
        string $name,
        string $category,
        string $status,
        string $acquisitionValue,
        string $serialNumber,
        string $building,
        string $room,
        string $supplierId,
        string $supplierName,
        string $lastMaintenance,
        string $nextMaintenance,
        string $maintenanceNote,
        array $recordedBy,
    ): array {
        $lastMaintenanceDate = Carbon::parse($lastMaintenance);
        $nextMaintenanceDate = Carbon::parse($nextMaintenance);
        $previousMaintenanceDate = $lastMaintenanceDate->copy()->subYear();

        return [
            'asset_number' => $assetNumber,
            'name' => $name,
            'category' => $category,
            'status' => $status,
            'acquisition_value' => $acquisitionValue,
            'currency' => 'CHF',
            'serial_number' => $serialNumber,
            'location' => [
                'site' => 'Hauptsitz',
                'building' => $building,
                'room' => $room,
            ],
            'supplier' => [
                'external_id' => $supplierId,
                'name' => $supplierName,
            ],
            'maintenance' => [
                'last_completed_at' => new UTCDateTime($lastMaintenanceDate),
                'next_due_at' => new UTCDateTime($nextMaintenanceDate),
                'interval_days' => 365,
                'note' => $maintenanceNote,
            ],
            'maintenance_history' => [
                $this->maintenanceHistoryEntry(
                    completedAt: $lastMaintenanceDate,
                    nextDueAt: $nextMaintenanceDate,
                    statusAfter: $status,
                    note: $maintenanceNote,
                    recordedBy: $recordedBy,
                ),
                $this->maintenanceHistoryEntry(
                    completedAt: $previousMaintenanceDate,
                    nextDueAt: $lastMaintenanceDate,
                    statusAfter: Asset::STATUS_ACTIVE,
                    note: 'Planmässige Jahreswartung ohne Beanstandungen.',
                    recordedBy: $recordedBy,
                ),
            ],
            'acquired_at' => '2022-01-01',
            'warranty_until' => '2027-12-31',
        ];
    }

    /**
     * @param  array{id: string|null, name: string}  $recordedBy
     * @return array<string, mixed>
     */
    private function maintenanceHistoryEntry(
        Carbon $completedAt,
        Carbon $nextDueAt,
        string $statusAfter,
        string $note,
        array $recordedBy,
    ): array {
        return [
            '_id' => new ObjectId,
            'completed_at' => new UTCDateTime($completedAt),
            'next_due_at' => new UTCDateTime($nextDueAt),
            'interval_days' => 365,
            'status_after' => $statusAfter,
            'note' => $note,
            'recorded_by' => $recordedBy,
            'recorded_at' => new UTCDateTime($completedAt->copy()->setTime(16, 0)),
        ];
    }
}
