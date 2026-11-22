<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('strategic_pillar_id')->nullable()->constrained('strategic_pillars')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('program_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('slug');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('target_groups')->nullable();
            $table->string('geographic_coverage')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['program_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('strategic_pillar_id')->nullable()->constrained('strategic_pillars')->nullOnDelete();
            $table->string('status', 32)->default('planned');
            $table->string('workflow_status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['workflow_status', 'published_at']);
            $table->index('status');
        });

        Schema::create('project_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('slug');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('target_groups')->nullable();
            $table->string('geographic_coverage')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('article_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('article_category_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_category_id')->constrained('article_categories')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['article_category_id', 'locale']);
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_category_id')->nullable()->constrained('article_categories')->nullOnDelete();
            $table->foreignId('strategic_pillar_id')->nullable()->constrained('strategic_pillars')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->date('event_date')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('article_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('slug');
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('tag_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->timestamps();

            $table->unique(['tag_id', 'locale']);
        });

        Schema::create('article_tag', function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();

            $table->primary(['article_id', 'tag_id']);
        });

        Schema::create('publications', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 48);
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->date('publication_date')->nullable();
            $table->foreignId('strategic_pillar_id')->nullable()->constrained('strategic_pillars')->nullOnDelete();
            $table->foreignId('document_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('version', 32)->nullable();
            $table->boolean('is_restricted')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('category');
        });

        Schema::create('publication_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('publication_id')->constrained('publications')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('slug');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['publication_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        $pivots = [
            'page_media' => ['page_id', 'pages'],
            'pillar_media' => ['strategic_pillar_id', 'strategic_pillars'],
            'project_media' => ['project_id', 'projects'],
            'article_media' => ['article_id', 'articles'],
            'publication_media' => ['publication_id', 'publications'],
        ];

        foreach ($pivots as $pivot => [$foreignKey, $ownerTable]) {
            Schema::create($pivot, function (Blueprint $table) use ($foreignKey, $ownerTable): void {
                $table->foreignId($foreignKey)->constrained($ownerTable)->cascadeOnDelete();
                $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
                $table->string('usage', 32)->default('gallery');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique([$foreignKey, 'media_id', 'usage']);
            });
        }
    }

    public function down(): void
    {
        foreach (['publication_media', 'article_media', 'project_media', 'pillar_media', 'page_media'] as $pivot) {
            Schema::dropIfExists($pivot);
        }

        Schema::dropIfExists('publication_translations');
        Schema::dropIfExists('publications');
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('tag_translations');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('article_translations');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_category_translations');
        Schema::dropIfExists('article_categories');
        Schema::dropIfExists('project_translations');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('program_translations');
        Schema::dropIfExists('programs');
    }
};
