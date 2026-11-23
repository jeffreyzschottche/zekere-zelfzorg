<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('care_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_execution_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users');
            $table->string('outcome');
            $table->text('note');
            $table->dateTime('reviewed_at');
        });
        Schema::create('care_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('reviewed_by')->constrained('users');
            $table->string('template');
            $table->string('version');
            $table->string('status');
            $table->string('method');
            $table->text('note');
            $table->dateTime('reviewed_at');
            $table->index(['care_profile_id', 'user_id', 'template']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_skills');
        Schema::dropIfExists('care_reviews');
    }
};
