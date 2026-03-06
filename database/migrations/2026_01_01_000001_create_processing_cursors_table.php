<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('skill_email-management')->create('processing_cursors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('mailbox_id');
            $table->string('cursor_type')->default('triage');
            $table->string('cursor_value')->nullable();
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mailbox_id', 'cursor_type']);
        });
    }

    public function down(): void
    {
        Schema::connection('skill_email-management')->dropIfExists('processing_cursors');
    }
};
