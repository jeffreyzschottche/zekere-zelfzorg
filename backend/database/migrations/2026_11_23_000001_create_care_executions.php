<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('care_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users');
            $table->date('day');
            $table->string('status');
            $table->string('version');
            $table->json('answers');
            $table->json('opened_steps');
            $table->text('note')->nullable();
            $table->dateTime('performed_at')->nullable();
            $table->dateTime('recorded_at');
            $table->boolean('retrospective');
            $table->unique(['care_assignment_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_executions');
    }
};
