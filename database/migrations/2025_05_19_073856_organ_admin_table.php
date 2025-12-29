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
        Schema::create('organ_admin', function (Blueprint $table) {
            $table->foreignId('organ_id')->constrained('organs')->onDelete('restrict');
            $table->foreignId('admin_id')->constrained('users')->onDelete('restrict');
            $table->primary(['organ_id', 'admin_id']);
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organ_admin');
    }
};
