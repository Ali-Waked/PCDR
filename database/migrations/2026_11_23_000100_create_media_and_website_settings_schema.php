<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->string('disk');
            $table->string('path');
            $table->string('type', 24);
            $table->string('mime_type', 128);
            $table->string('extension', 16);
            $table->unsignedInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('checksum', 64);
            $table->string('original_name');
            $table->boolean('is_restricted')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'is_restricted']);
            $table->index('mime_type');
        });

        Schema::create('media_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'locale']);
        });

        Schema::create('website_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 48);
            $table->string('key', 96)->unique();
            $table->string('type', 32)->default('string');
            $table->boolean('is_localized')->default(false);
            $table->boolean('is_public')->default(false);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index('group');
        });

        Schema::create('website_setting_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_setting_id')->constrained('website_settings')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['website_setting_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_setting_translations');
        Schema::dropIfExists('website_settings');
        Schema::dropIfExists('media_translations');
        Schema::dropIfExists('media');
    }
};
