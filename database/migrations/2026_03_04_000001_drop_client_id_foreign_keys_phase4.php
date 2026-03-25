<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4: Drop foreign key constraints to host 'clients' table.
 * Column client_id is kept; only the FK is removed so the package does not require a host table.
 * New installs use unsignedBigInteger in the original migrations; this migration is for existing installs.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'officeguy_crm_entities',
            'officeguy_sumit_webhooks',
            'officeguy_crm_activities',
        ];

        foreach ($tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            if (! Schema::hasColumn($tableName, 'client_id')) {
                continue;
            }

            $constraintName = sprintf('%s_client_id_foreign', $tableName);

            if (DB::connection()->getDriverName() === 'pgsql') {
                // In Postgres the constraint drop supports IF EXISTS.
                DB::statement(sprintf(
                    'ALTER TABLE "%s" DROP CONSTRAINT IF EXISTS "%s"',
                    $tableName,
                    $constraintName,
                ));

                continue;
            }

            // Fallback for non-Postgres drivers.
            Schema::table($tableName, function (Blueprint $table): void {
                try {
                    $table->dropForeign(['client_id']);
                } catch (Throwable) {
                    // FK may already be missing (e.g. new install) or named differently
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally no-op: we do not re-add FK to host table
    }
};
