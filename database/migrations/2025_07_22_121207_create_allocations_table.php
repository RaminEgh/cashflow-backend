<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organ_id')->constrained()->onDelete('cascade');
            $table->unsignedSmallInteger('year');
            $table->unique(['organ_id', 'year']);
            $table->unsignedBigInteger('month_1_budget');
            $table->unsignedBigInteger('month_2_budget');
            $table->unsignedBigInteger('month_3_budget');
            $table->unsignedBigInteger('month_4_budget');
            $table->unsignedBigInteger('month_5_budget');
            $table->unsignedBigInteger('month_6_budget');
            $table->unsignedBigInteger('month_7_budget');
            $table->unsignedBigInteger('month_8_budget');
            $table->unsignedBigInteger('month_9_budget');
            $table->unsignedBigInteger('month_10_budget');
            $table->unsignedBigInteger('month_11_budget');
            $table->unsignedBigInteger('month_12_budget');
            $table->unsignedBigInteger('month_1_expense');
            $table->unsignedBigInteger('month_2_expense');
            $table->unsignedBigInteger('month_3_expense');
            $table->unsignedBigInteger('month_4_expense');
            $table->unsignedBigInteger('month_5_expense');
            $table->unsignedBigInteger('month_6_expense');
            $table->unsignedBigInteger('month_7_expense');
            $table->unsignedBigInteger('month_8_expense');
            $table->unsignedBigInteger('month_9_expense');
            $table->unsignedBigInteger('month_10_expense');
            $table->unsignedBigInteger('month_11_expense');
            $table->unsignedBigInteger('month_12_expense');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocations');
    }
};
