<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('page_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('slug');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
            $table->string('robots', 32)->default('index,follow');
            $table->timestamps();

            $table->unique(['page_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('strategic_pillars', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 4)->unique();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('strategic_pillar_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('strategic_pillar_id')->constrained('strategic_pillars')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('slug');
            $table->string('title');
            $table->string('short_title')->nullable();
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('target_groups')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['strategic_pillar_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('content_revisions', function (Blueprint $table): void {
            $table->id();
            $table->string('revisionable_type');
            $table->unsignedBigInteger('revisionable_id');
            $table->string('locale', 8);
            $table->string('title')->nullable();
            $table->string('status', 32)->nullable();
            $table->json('payload');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['revisionable_type', 'revisionable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('strategic_pillar_translations');
        Schema::dropIfExists('strategic_pillars');
        Schema::dropIfExists('page_translations');
        Schema::dropIfExists('pages');
    }
};
