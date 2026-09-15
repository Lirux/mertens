<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Collection;
use MongoDB\Laravel\Connection;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Bestehende Konten erhalten Leserechte, niemals automatisch Schreibrechte.
     */
    public function up(): void
    {
        $this->users()->updateMany(
            ['role' => ['$exists' => false]],
            ['$set' => ['role' => 'employee']],
        );
    }

    public function down(): void
    {
        $this->users()->updateMany([], ['$unset' => ['role' => '']]);
    }

    private function users(): Collection
    {
        $connection = DB::connection('mongodb');

        if (! $connection instanceof Connection) {
            throw new RuntimeException('The user role migration requires the MongoDB connection.');
        }

        return $connection->getCollection('users');
    }
};
