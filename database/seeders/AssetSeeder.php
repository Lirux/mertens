<?php

namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

class AssetSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->assets() as $asset) {
            Asset::query()->updateOrCreate(
                ['asset_number' => $asset['asset_number']],
                $asset,
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function assets(): array
    {
        return [
            $this->asset(
                assetNumber: 'AST-00001',
                name: 'CNC Fräsmaschine DMU 50',
                category: 'production',
                status: Asset::STATUS_ACTIVE,
                serialNumber: 'SN-DMU50-001',
                building: 'A',
                room: '101',
                supplierId: 'SUP-0001',
                supplierName: 'DMG MORI Schweiz AG',
                lastMaintenance: '2026-02-15',
                nextMaintenance: '2027-02-15',
            ),
            $this->asset(
                assetNumber: 'AST-00002',
                name: 'Schraubenkompressor GA 22',
                category: 'production',
                status: Asset::STATUS_MAINTENANCE,
                serialNumber: 'SN-GA22-014',
                building: 'A',
                room: '108',
                supplierId: 'SUP-0002',
                supplierName: 'Atlas Copco (Schweiz) AG',
                lastMaintenance: '2025-08-20',
                nextMaintenance: '2026-08-20',
            ),
            $this->asset(
                assetNumber: 'AST-00003',
                name: 'Elektro-Gabelstapler E20',
                category: 'logistics',
                status: Asset::STATUS_ACTIVE,
                serialNumber: 'SN-E20-207',
                building: 'B',
                room: 'Lager',
                supplierId: 'SUP-0003',
                supplierName: 'Linde Material Handling Schweiz AG',
                lastMaintenance: '2026-04-10',
                nextMaintenance: '2027-04-10',
            ),
            $this->asset(
                assetNumber: 'AST-00004',
                name: 'Koordinatenmessgerät CRYSTA-Apex',
                category: 'measurement',
                status: Asset::STATUS_OUT_OF_SERVICE,
                serialNumber: 'SN-CRYSTA-032',
                building: 'C',
                room: '205',
                supplierId: 'SUP-0004',
                supplierName: 'Mitutoyo (Schweiz) AG',
                lastMaintenance: '2025-11-01',
                nextMaintenance: '2026-11-01',
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function asset(
        string $assetNumber,
        string $name,
        string $category,
        string $status,
        string $serialNumber,
        string $building,
        string $room,
        string $supplierId,
        string $supplierName,
        string $lastMaintenance,
        string $nextMaintenance,
    ): array {
        return [
            'asset_number' => $assetNumber,
            'name' => $name,
            'category' => $category,
            'status' => $status,
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
                'last_completed_at' => new UTCDateTime(Carbon::parse($lastMaintenance)),
                'next_due_at' => new UTCDateTime(Carbon::parse($nextMaintenance)),
                'interval_days' => 365,
            ],
            'acquired_at' => '2022-01-01',
            'warranty_until' => '2027-12-31',
        ];
    }
}
