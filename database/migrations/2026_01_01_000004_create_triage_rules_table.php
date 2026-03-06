<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('skill_email-management')->create('triage_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('match_type')->default('sender');
            $table->string('match_value');
            $table->string('target_category');
            $table->boolean('auto_acknowledge')->default(false);
            $table->unsignedInteger('priority_override')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::connection('skill_email-management')->dropIfExists('triage_rules');
    }
};
