<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->id();
            $table->string('priority');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('response_hours')->default(0);
            $table->unsignedSmallInteger('response_minutes')->default(0);
            $table->unsignedSmallInteger('resolution_hours')->default(0);
            $table->unsignedSmallInteger('resolution_minutes')->default(0);
            $table->unsignedSmallInteger('escalation_hours')->default(0);
            $table->unsignedSmallInteger('escalation_minutes')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_rules');
    }
};
