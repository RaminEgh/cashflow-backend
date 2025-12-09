<?php

use App\Enums\BalanceStatus;
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
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_id')->constrained('deposits');
            $table->timestamp('fetched_at');
            $table->timestamp('rahkaran_fetched_at')->nullable();
            $table->enum('status', [BalanceStatus::Fail->value, BalanceStatus::Success->value])->default(BalanceStatus::Fail->value);
            $table->enum('rahkaran_status', [BalanceStatus::Fail->value, BalanceStatus::Success->value])->default(BalanceStatus::Fail->value);
            $table->unsignedBigInteger('balance')->nullable();
            $table->unsignedBigInteger('rahkaran_balance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balances');
    }
};
