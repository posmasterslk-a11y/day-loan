<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('account_type');
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('dr', 12, 2)->default(0);
            $table->decimal('cr', 12, 2)->default(0);
            $table->string('type'); // 'OFFICER', 'MAIN', 'BANK'
            $table->foreignId('bank_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
