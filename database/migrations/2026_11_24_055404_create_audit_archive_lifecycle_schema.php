<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_archives', function (Blueprint $table): void {
            $table->id();
            $table->string('period_key', 7)->unique();
            $table->string('name', 120);
            $table->timestamp('period_start');
            $table->timestamp('period_end')->comment('Exclusive upper boundary.');
            $table->timestamp('cutoff_at');
            $table->unsignedInteger('record_count')->default(0);
            $table->string('file_path', 255);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->json('checksums')->nullable();
            $table->json('manifest')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('schema_version', 20)->default('1.0');
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('pruned_at')->nullable();
            $table->timestamp('last_cleanup_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'period_start']);
        });

        Schema::create('audit_cleanup_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('audit_archive_id')->nullable()->constrained('audit_archives')->nullOnDelete();
            $table->string('status', 20)->default('processing');
            $table->string('mode', 20)->default('execute');
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('eligible_count')->default(0);
            $table->unsignedInteger('deleted_count')->default(0);
            $table->unsignedInteger('skipped_hold_count')->default(0);
            $table->unsignedInteger('skipped_unverified_count')->default(0);
            $table->unsignedBigInteger('estimated_bytes')->nullable();
            $table->json('report')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['audit_archive_id', 'status']);
        });

        Schema::create('audit_log_holds', function (Blueprint $table): void {
            $table->id();
            $table->string('label', 160);
            $table->text('reason');
            $table->string('scope_type', 40);
            $table->string('subject_type', 160)->nullable();
            $table->string('subject_id', 160)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scope_type', 'expires_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreignId('archive_id')->nullable()->after('request_id')->constrained('audit_archives')->nullOnDelete();
            $table->timestamp('archived_at')->nullable()->after('archive_id');

            $table->index(['archive_id', 'created_at']);
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropForeign(['archive_id']);
            $table->dropIndex(['archive_id', 'created_at']);
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['archive_id', 'archived_at']);
        });

        Schema::dropIfExists('audit_log_holds');
        Schema::dropIfExists('audit_cleanup_runs');
        Schema::dropIfExists('audit_archives');
    }
};
