<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Partial index for active subscribers (PostgreSQL-specific)
        DB::statement("CREATE INDEX idx_subscribers_active ON subscribers (email) WHERE status = 'active'");

        // GIN indexes on JSONB columns
        DB::statement('CREATE INDEX idx_subscribers_metadata ON subscribers USING GIN (metadata)');
        DB::statement('CREATE INDEX idx_delivery_logs_payload ON delivery_logs USING GIN (payload)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_subscribers_active');
        DB::statement('DROP INDEX IF EXISTS idx_subscribers_metadata');
        DB::statement('DROP INDEX IF EXISTS idx_delivery_logs_payload');
    }
};
