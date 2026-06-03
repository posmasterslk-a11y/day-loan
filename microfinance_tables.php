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
        // Customers Table
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id')->unique();
            $table->string('full_name');
            $table->string('nic')->unique();
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone');
            $table->string('whatsapp')->nullable();
            $table->text('address')->nullable();
            $table->string('business_type')->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('nic_front_path')->nullable();
            $table->string('nic_back_path')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });

        // Guarantors Table
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('nic')->unique();
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('relationship')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        // Customer Guarantor Pivot Table
        Schema::create('customer_guarantor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('guarantor_id')->constrained()->onDelete('cascade');
        });

        // Loans Table
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->decimal('interest_rate', 5, 2); // percentage
            $table->date('start_date');
            $table->integer('term_days')->default(60);
            $table->decimal('total_payable', 12, 2);
            $table->decimal('daily_installment', 12, 2);
            $table->enum('status', ['Pending', 'Approved', 'Active', 'Completed', 'Overdue', 'Rejected'])->default('Pending');
            $table->timestamps();
        });

        // Loan Schedules Table
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->date('due_date');
            $table->decimal('amount_due', 12, 2);
            $table->enum('status', ['Pending', 'Paid', 'Missed', 'Partial'])->default('Pending');
            $table->timestamps();
        });

        // Collections Table
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('collector_id'); // Assuming references users table later
            $table->decimal('amount_paid', 12, 2);
            $table->date('payment_date');
            $table->decimal('gps_lat', 10, 8)->nullable();
            $table->decimal('gps_long', 11, 8)->nullable();
            $table->text('remarks')->nullable();
            $table->string('receipt_number')->unique();
            $table->enum('type', ['Full', 'Partial', 'Missed'])->default('Full');
            $table->timestamps();
        });

        // Audit Logs Table
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('loan_schedules');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('customer_guarantor');
        Schema::dropIfExists('guarantors');
        Schema::dropIfExists('customers');
    }
};
