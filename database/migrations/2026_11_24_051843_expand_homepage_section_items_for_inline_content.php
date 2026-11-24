<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_section_items', function (Blueprint $table): void {
            $table->string('item_key', 64)->nullable()->after('item_type');
            $table->boolean('is_published')->default(true)->after('item_key');
            $table->decimal('numeric_value', 20, 4)->nullable()->after('is_published');
            $table->unique(['homepage_section_id', 'item_key'], 'homepage_section_items_section_key_unique');
        });

        Schema::create('homepage_section_item_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('homepage_section_item_id')->constrained('homepage_section_items')->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('label')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('display_value')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->unique(['homepage_section_item_id', 'locale'], 'homepage_section_item_translations_item_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_section_item_translations');

        Schema::table('homepage_section_items', function (Blueprint $table): void {
            $table->dropUnique('homepage_section_items_section_key_unique');
            $table->dropColumn(['item_key', 'is_published', 'numeric_value']);
        });
    }
};
