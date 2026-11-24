<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory_partner_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('directory_partner_category_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('directory_partner_category_id')->constrained('directory_partner_categories')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['directory_partner_category_id', 'locale']);
        });

        Schema::create('directory_partners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('directory_partner_category_id')->nullable()
                ->constrained('directory_partner_categories')->nullOnDelete();
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('website_url', 500)->nullable();
            $table->unsignedSmallInteger('partnership_year')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['directory_partner_category_id', 'display_order']);
        });

        Schema::create('directory_partner_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('directory_partner_id')->constrained('directory_partners')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['directory_partner_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_partner_translations');
        Schema::dropIfExists('directory_partners');
        Schema::dropIfExists('directory_partner_category_translations');
        Schema::dropIfExists('directory_partner_categories');
    }
};
