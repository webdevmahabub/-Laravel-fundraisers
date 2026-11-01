<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add a 'suspended' status so admins can halt a campaign without deleting it.
        // Raw ALTER avoids a doctrine/dbal dependency for enum modification.
        if (Schema::hasTable('campaigns')) {
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM(
                'draft', 'pending', 'active', 'paused', 'completed', 'cancelled', 'rejected', 'suspended'
            ) NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('campaigns')) {
            // The narrow enum below cannot hold 'suspended'. Stepping suspended
            // campaigns down to 'paused' first keeps this rollback data-safe —
            // the test harness re-runs down() on a database that still holds
            // rows, and past suspended campaigns would otherwise abort it.
            DB::table('campaigns')->where('status', 'suspended')->update(['status' => 'paused']);

            DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM(
                'draft', 'pending', 'active', 'paused', 'completed', 'cancelled', 'rejected'
            ) NOT NULL DEFAULT 'draft'");
        }
    }
};
