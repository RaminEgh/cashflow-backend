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
            $table->unsignedBigInteger('organ_id');
            $table->unsignedBigInteger('admin_id'); // user_id
            $table->primary(['organ_id', 'admin_id']);
            $table->foreign('organ_id')->references('id')->on('organs')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('organ_admin');
    }
};
