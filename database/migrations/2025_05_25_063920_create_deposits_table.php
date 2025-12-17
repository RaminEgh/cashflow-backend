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
            $table->unsignedBigInteger('organ_id');
            $table->unsignedBigInteger('bank_id');
            $table->mediumInteger('branch_code');
            $table->string('branch_name');
            $table->string('number');
            $table->unsignedBigInteger('balance')->nullable();
            $table->unsignedBigInteger('rahkaran_balance')->nullable();
            $table->timestamp('balance_last_synced_at')->nullable();
            $table->timestamp('rahkaran_balance_last_synced_at')->nullable();
            $table->string('sheba')->nullable();
            $table->enum('type', DepositType::values());
            $table->string('currency');
            $table->string('description')->nullable();
            $table->boolean('has_access_banking_api')->default(false);
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
        Schema::dropIfExists('deposits');
    }
};
