<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('skill_email-management')->create('attention_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('mailbox_id');
            $table->string('message_id');
            $table->string('priority')->default('normal');
            $table->text('reason');
            $table->string('category')->nullable();
            $table->string('subject')->nullable();
            $table->string('sender')->nullable();
            $table->boolean('acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('surfaced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'acknowledged']);
            $table->index(['user_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::connection('skill_email-management')->dropIfExists('attention_items');
    }
};
