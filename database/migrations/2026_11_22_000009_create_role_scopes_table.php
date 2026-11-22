<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_scopes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('resource_key', 120);
            $table->string('scope_type', 40);
            $table->json('scope_value')->nullable();
            $table->string('scope_value_hash', 64);
            $table->timestamps();

            $table->unique(['role_id', 'resource_key', 'scope_type', 'scope_value_hash'], 'role_scope_unique');
            $table->index(['resource_key', 'scope_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_scopes');
    }
};
