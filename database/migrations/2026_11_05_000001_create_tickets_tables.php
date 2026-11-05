<?php

use App\Tickets\Support\TicketAttachmentStorage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        TicketAttachmentStorage::purgeAll();

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 200);
            $table->string('department', 30)->default('technical');
            $table->string('priority', 20)->default('medium');
            $table->string('status', 30)->default('open');
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'last_reply_at']);
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_admin_reply')->default(false);
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'id']);
        });
    }

    public function down(): void
    {
        TicketAttachmentStorage::purgeAll();

        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }
};
