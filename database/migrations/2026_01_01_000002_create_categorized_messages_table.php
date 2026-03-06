<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('skill_email-management')->create('categorized_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('mailbox_id');
            $table->string('message_id');
            $table->string('category');
            $table->float('confidence')->default(0.0);
            $table->string('subject')->nullable();
            $table->string('sender')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('message_date')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mailbox_id', 'message_id']);
            $table->index(['user_id', 'category']);
            $table->index(['user_id', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('skill_email-management')->dropIfExists('categorized_messages');
    }
};
