<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('subject');
            $table->string('customer_name');
            $table->enum('priority', ['High', 'Medium', 'Low', 'General'])->default('General');
            $table->enum('status', ['open', 'pending', 'resolved'])->default('open');
            $table->string('assigned_to')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
