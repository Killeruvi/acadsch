<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Infix School Management System modules.
     */
    public function up(): void
    {
        // 1. Library Books
        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->string('book_title');
            $table->string('book_number')->unique();
            $table->string('isbn_number')->nullable();
            $table->string('publisher_name')->nullable();
            $table->string('author_name')->nullable();
            $table->foreignId('subject_id')->nullable()->constrained('academic_subjects')->nullOnDelete();
            $table->string('rack_number')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('available_quantity')->default(1);
            $table->decimal('book_price', 10, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Book Issues
        Schema::create('book_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->string('status', 30)->default('issued'); // issued, returned, lost
            $table->decimal('fine_amount', 10, 2)->default(0.00);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 3. Transport Routes
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_title');
            $table->decimal('fare', 10, 2)->default(0.00);
            $table->string('start_point')->nullable();
            $table->string('end_point')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 4. Transport Vehicles
        Schema::create('transport_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_number')->unique();
            $table->string('vehicle_model')->nullable();
            $table->string('year_made')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_license')->nullable();
            $table->string('driver_phone')->nullable();
            $table->foreignId('route_id')->nullable()->constrained('transport_routes')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 5. Dormitories
        Schema::create('dormitories', function (Blueprint $table) {
            $table->id();
            $table->string('dormitory_name');
            $table->string('type', 30)->default('mixed'); // boys, girls, mixed
            $table->string('address')->nullable();
            $table->integer('intake_capacity')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 6. Dormitory Rooms
        Schema::create('dormitory_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dormitory_id')->constrained('dormitories')->cascadeOnDelete();
            $table->string('room_number');
            $table->string('room_type', 30)->default('double'); // single, double, dorm
            $table->integer('number_of_beds')->default(2);
            $table->decimal('cost_per_bed', 10, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 7. School Incomes
        Schema::create('school_incomes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('income_head', 60)->default('Miscellaneous');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('payment_method', 40)->default('cash');
            $table->string('invoice_number')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 8. School Expenses
        Schema::create('school_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('expense_head', 60)->default('Miscellaneous');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('payment_method', 40)->default('cash');
            $table->string('voucher_number')->nullable();
            $table->text('description')->nullable();
            $table->string('paid_to')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 9. Staff Payrolls
        Schema::create('staff_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->string('payroll_month', 20); // e.g. "2026-11"
            $table->decimal('basic_salary', 10, 2)->default(0.00);
            $table->decimal('earnings', 10, 2)->default(0.00);
            $table->decimal('deductions', 10, 2)->default(0.00);
            $table->decimal('gross_salary', 10, 2)->default(0.00);
            $table->decimal('net_salary', 10, 2)->default(0.00);
            $table->string('payment_method', 40)->default('bank_transfer');
            $table->string('payment_status', 30)->default('generated'); // generated, paid
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 10. Study Materials (Download Center)
        Schema::create('study_materials', function (Blueprint $table) {
            $table->id();
            $table->string('content_title');
            $table->string('content_type', 40)->default('study_material'); // assignment, syllabus, study_material, other_download
            $table->foreignId('class_id')->nullable()->constrained('academic_classes')->nullOnDelete();
            $table->string('available_for', 30)->default('all'); // all, students, teachers
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->date('upload_date');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 11. Front Office Visitors
        Schema::create('front_office_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('id_proof')->nullable();
            $table->string('purpose');
            $table->string('person_to_meet')->nullable();
            $table->string('in_time', 20)->nullable();
            $table->string('out_time', 20)->nullable();
            $table->date('date');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 12. Front Office Calls
        Schema::create('front_office_calls', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->date('date');
            $table->text('description')->nullable();
            $table->date('next_follow_up_date')->nullable();
            $table->string('call_duration', 30)->nullable();
            $table->string('call_type', 30)->default('incoming'); // incoming, outgoing
            $table->timestamps();
        });

        // 13. Front Office Postal Dispatches
        Schema::create('front_office_postal_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('from_title');
            $table->string('reference_no')->nullable();
            $table->string('address')->nullable();
            $table->string('to_title');
            $table->date('date');
            $table->string('type', 30)->default('dispatch'); // dispatch, receive
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('front_office_postal_dispatches');
        Schema::dropIfExists('front_office_calls');
        Schema::dropIfExists('front_office_visitors');
        Schema::dropIfExists('study_materials');
        Schema::dropIfExists('staff_payrolls');
        Schema::dropIfExists('school_expenses');
        Schema::dropIfExists('school_incomes');
        Schema::dropIfExists('dormitory_rooms');
        Schema::dropIfExists('dormitories');
        Schema::dropIfExists('transport_vehicles');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('book_issues');
        Schema::dropIfExists('library_books');
    }
};
