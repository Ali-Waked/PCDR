<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 80);
            $table->string('key', 160);
            $table->string('type', 32);
            $table->text('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_localized')->default(false);
            $table->boolean('is_secret_reference')->default(false);
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        Schema::create('setting_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('setting_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['setting_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_translations');
        Schema::dropIfExists('settings');
    }
};
