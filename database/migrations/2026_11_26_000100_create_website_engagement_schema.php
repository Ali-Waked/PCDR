<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['contact_messages', 'volunteer_inquiries', 'partnership_inquiries'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->foreignId('assigned_to')->nullable()->after('status');
                $blueprint->index('assigned_to');
            });
        }

        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 96)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('email_template_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('email_template_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name', 160);
            $table->string('description', 500)->nullable();
            $table->string('subject', 255);
            $table->text('body');
            $table->timestamps();

            $table->unique(['email_template_id', 'locale']);
        });

        Schema::create('inquiry_notes', function (Blueprint $table): void {
            $table->id();
            $table->string('inquiry_type', 32);
            $table->unsignedBigInteger('inquiry_id');
            $table->foreignId('user_id')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['inquiry_type', 'inquiry_id']);
        });

        Schema::create('inquiry_replies', function (Blueprint $table): void {
            $table->id();
            $table->string('inquiry_type', 32);
            $table->unsignedBigInteger('inquiry_id');
            $table->string('reference', 64)->nullable();
            $table->string('recipient', 255);
            $table->foreignId('sender_user_id')->nullable();
            $table->foreignId('email_template_id')->nullable();
            $table->string('subject', 255);
            $table->text('body');
            $table->string('locale', 8);
            $table->string('status', 24)->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['inquiry_type', 'inquiry_id']);
        });

        Schema::create('email_delivery_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('idempotency_key', 128)->unique();
            $table->string('purpose', 48);
            $table->string('template_key', 96)->nullable();
            $table->string('inquiry_type', 32)->nullable();
            $table->unsignedBigInteger('inquiry_id')->nullable();
            $table->string('inquiry_reference', 64)->nullable();
            $table->string('recipient_hash', 64);
            $table->string('locale', 8);
            $table->string('status', 24);
            $table->string('provider', 64)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->text('error_code')->nullable();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->foreignId('sender_user_id')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_delivery_logs');
        Schema::dropIfExists('inquiry_replies');
        Schema::dropIfExists('inquiry_notes');
        Schema::dropIfExists('email_template_translations');
        Schema::dropIfExists('email_templates');

        foreach (['contact_messages', 'volunteer_inquiries', 'partnership_inquiries'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropIndex(['assigned_to']);
                $blueprint->dropColumn('assigned_to');
            });
        }
    }
};
