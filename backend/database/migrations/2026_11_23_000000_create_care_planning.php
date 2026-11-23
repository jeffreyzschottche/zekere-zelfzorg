<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('care_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('care_members', function (Blueprint $table) {
            $table->foreignId('care_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->primary(['care_profile_id', 'user_id']);
        });
        Schema::create('care_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignee_id')->constrained('users');
            $table->string('template');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('time', 5);
            $table->string('repeat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_assignments');
        Schema::dropIfExists('care_members');
        Schema::dropIfExists('care_profiles');
    }
};
