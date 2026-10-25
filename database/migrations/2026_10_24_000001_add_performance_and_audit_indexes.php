<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existingIndexes = collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'index'"))->pluck('name')->toArray();

        Schema::table('members', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('members_status_index', $existingIndexes)) {
                $table->index('status', 'members_status_index');
            }
            if (!in_array('members_has_debt_index', $existingIndexes)) {
                $table->index('has_debt', 'members_has_debt_index');
            }
        });

        Schema::table('shop_cash_shifts', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('shop_cash_shifts_status_index', $existingIndexes)) {
                $table->index('status', 'shop_cash_shifts_status_index');
            }
            if (!in_array('shop_cash_shifts_opened_at_index', $existingIndexes)) {
                $table->index('opened_at', 'shop_cash_shifts_opened_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shop_cash_shifts', function (Blueprint $table) {
            $table->dropIndex('shop_cash_shifts_status_index');
            $table->dropIndex('shop_cash_shifts_opened_at_index');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('members_status_index');
            $table->dropIndex('members_has_debt_index');
        });
    }
};
