<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_menus', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 48)->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('navigation_menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->cascadeOnDelete();
            $table->string('link_type', 24);
            $table->string('link_value');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('icon', 48)->nullable();
            $table->timestamps();

            $table->index(['navigation_menu_id', 'is_enabled', 'display_order']);
        });

        Schema::create('navigation_item_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('navigation_item_id')->constrained('navigation_items')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('label');
            $table->timestamps();

            $table->unique(['navigation_item_id', 'locale']);
        });

        Schema::create('homepage_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('type', 48);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('homepage_section_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('homepage_section_id')->constrained('homepage_sections')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('eyebrow')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_link')->nullable();
            $table->timestamps();

            $table->unique(['homepage_section_id', 'locale']);
        });

        Schema::create('homepage_section_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('homepage_section_id')->constrained('homepage_sections')->cascadeOnDelete();
            $table->string('item_type', 32);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['homepage_section_id', 'sort_order']);
        });

        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();
            $table->ulid('reference')->unique();
            $table->string('category', 48);
            $table->string('name');
            $table->string('email');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('locale', 8);
            $table->boolean('consent')->default(false);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('status', 24)->default('new');
            $table->timestamps();
        });

        Schema::create('volunteer_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->ulid('reference')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('area_of_interest', 96);
            $table->string('availability', 48);
            $table->text('message')->nullable();
            $table->string('locale', 8);
            $table->boolean('consent')->default(false);
            $table->string('ip_hash', 64)->nullable();
            $table->string('status', 24)->default('new');
            $table->timestamps();
        });

        Schema::create('partnership_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->ulid('reference')->unique();
            $table->string('organization');
            $table->string('contact_name');
            $table->string('email');
            $table->string('website')->nullable();
            $table->string('interest', 96);
            $table->text('message');
            $table->string('locale', 8);
            $table->boolean('consent')->default(false);
            $table->string('ip_hash', 64)->nullable();
            $table->string('status', 24)->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partnership_inquiries');
        Schema::dropIfExists('volunteer_inquiries');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('homepage_section_items');
        Schema::dropIfExists('homepage_section_translations');
        Schema::dropIfExists('homepage_sections');
        Schema::dropIfExists('navigation_item_translations');
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_menus');
    }
};
