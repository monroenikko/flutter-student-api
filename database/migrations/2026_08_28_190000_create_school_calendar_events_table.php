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
        if (! Schema::hasTable('school_calendar_events')) {
            Schema::create('school_calendar_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->nullable()->comment('User ID who created event');
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('event_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('type', 20)->default('note')->comment('note, event, reminder, holiday');
                $table->boolean('is_broadcast')->default(0)->comment('1 = Broadcasted to all roles by Admin, 0 = Private/personal note');
                $table->string('audience', 30)->default('all')->comment('all, faculty, students, registrar, admission, finance');
                $table->boolean('created_by_admin')->default(0);
                $table->timestamps();

                $table->index(['event_date', 'type']);
                $table->index(['user_id', 'is_broadcast']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_calendar_events');
    }
};
