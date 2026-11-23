<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('care_assignments', function (Blueprint $table) {
            $table->unsignedInteger('followup_minutes')->nullable()->default(15);
            $table->string('followup_question')->default('observation');
            $table->string('followup_trigger')->default('yes');
            $table->string('followup_responder')->default('either');
        });
        Schema::create('care_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_execution_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('due_at');
            $table->string('question');
            $table->string('trigger');
            $table->string('responder');
            $table->foreignId('answered_by')->nullable()->constrained('users');
            $table->string('answer')->nullable();
            $table->dateTime('answered_at')->nullable();
        });
        Schema::create('care_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_execution_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->dateTime('created_at');
            $table->unique(['care_execution_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_alerts');
        Schema::dropIfExists('care_followups');
        Schema::table('care_assignments', fn (Blueprint $table) => $table->dropColumn([
            'followup_minutes', 'followup_question', 'followup_trigger', 'followup_responder',
        ]));
    }
};
