<?php

use App\Enums\DepositType;
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
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organ_id')->constrained('organs')->onDelete('restrict');
            $table->foreignId('bank_id')->constrained('banks')->onDelete('cascade');
            $table->mediumInteger('branch_code');
            $table->string('branch_name');
            $table->string('number');
            $table->unsignedBigInteger('balance')->nullable();
            $table->unsignedBigInteger('rahkaran_balance')->nullable();
            $table->timestamp('balance_synced_at')->nullable();
            $table->timestamp('rahkaran_synced_at')->nullable();
            $table->boolean('last_balance_sync_success')->default(false);
            $table->boolean('last_rahkaran_sync_success')->default(false);
            $table->string('sheba')->nullable();
            $table->enum('type', array_map('strval', DepositType::values()));
            $table->string('currency');
            $table->string('description')->nullable();
            $table->boolean('has_access_banking_api')->default(false);
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
        Schema::dropIfExists('deposits');
    }
};
