<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sla_rules', function (Blueprint $table) {
            if (Schema::hasColumn('sla_rules', 'escalation_hours')) {
                $table->dropColumn(['escalation_hours', 'escalation_minutes']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sla_rules', function (Blueprint $table) {
            $table->unsignedSmallInteger('escalation_hours')->default(0);
            $table->unsignedSmallInteger('escalation_minutes')->default(0);
        });
    }
};
