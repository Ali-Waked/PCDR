<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('status', 24)->default('active')->after('email');
            $table->string('locale', 8)->default('ar')->after('status');
            $table->boolean('is_platform_owner')->default(false)->after('locale');
            $table->timestamp('last_login_at')->nullable()->after('is_platform_owner');
            $table->timestamp('previous_login_at')->nullable()->after('last_login_at');
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('previous_login_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->softDeletes()->after('locked_until');

            $table->index(['status', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['status', 'deleted_at']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'status',
                'locale',
                'is_platform_owner',
                'last_login_at',
                'previous_login_at',
                'failed_login_attempts',
                'locked_until',
            ]);
        });
    }
};
