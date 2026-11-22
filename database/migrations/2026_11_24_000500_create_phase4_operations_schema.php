<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 operational foundation.
 *
 * No beneficiary, household, complaint, or institutional-approval tables are
 * created. Sensitive personal data is represented only by internal references
 * where an operational workflow genuinely requires a person link.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->extendEditorialTables();
        $this->createSharedTables();
        $this->createGeographyAndOperations();
        $this->createInventory();
        $this->createPeopleAndParticipation();
        $this->createPartnersAndGrants();
        $this->createMealAndResearch();
        $this->createReporting();
    }

    private function extendEditorialTables(): void
    {
        Schema::table('programs', function (Blueprint $table): void {
            $table->string('code', 64)->nullable()->unique()->after('id');
            $table->foreignId('program_manager_id')->nullable()->after('strategic_pillar_id')->constrained('users')->nullOnDelete();
            $table->string('responsible_unit', 255)->nullable()->after('program_manager_id');
            $table->string('category', 64)->nullable()->after('responsible_unit');
            $table->decimal('proposed_budget', 18, 2)->nullable()->after('category');
            $table->decimal('approved_budget', 18, 2)->nullable()->after('proposed_budget');
            $table->string('budget_currency', 3)->nullable()->after('approved_budget');
            $table->date('actual_start_date')->nullable()->after('end_date');
            $table->date('actual_end_date')->nullable()->after('actual_start_date');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->string('code', 64)->nullable()->unique()->after('id');
            $table->foreignId('project_manager_id')->nullable()->after('program_id')->constrained('users')->nullOnDelete();
            $table->string('responsible_team', 255)->nullable()->after('project_manager_id');
            $table->decimal('proposed_budget', 18, 2)->nullable()->after('responsible_team');
            $table->decimal('approved_budget', 18, 2)->nullable()->after('proposed_budget');
            $table->string('budget_currency', 3)->nullable()->after('approved_budget');
            $table->date('actual_start_date')->nullable()->after('end_date');
            $table->date('actual_end_date')->nullable()->after('actual_start_date');
        });
    }

    private function createSharedTables(): void
    {
        /**
         * Shared localisation for operational entities. The explicit
         * `resource` discriminator avoids a polymorphic type column while
         * allowing one audited, sanitised translation writer for all modules.
         */
        Schema::create('operational_translations', function (Blueprint $table): void {
            $table->id();
            $table->string('resource', 64);
            $table->unsignedBigInteger('owner_id');
            $table->string('locale', 8);
            $table->string('title')->nullable();
            $table->string('name')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('objectives')->nullable();
            $table->text('target_groups')->nullable();
            $table->longText('methodology')->nullable();
            $table->longText('findings')->nullable();
            $table->longText('recommendations')->nullable();
            $table->longText('lessons_learned')->nullable();
            $table->longText('conditions')->nullable();
            $table->longText('deliverables')->nullable();
            $table->longText('reporting_requirements')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['resource', 'owner_id', 'locale'], 'op_translations_owner_locale_unique');
            $table->index(['resource', 'locale'], 'op_translations_resource_locale_index');
        });

        /** Controlled status history shared by operational entities. */
        Schema::create('domain_transitions', function (Blueprint $table): void {
            $table->id();
            $table->string('resource', 64);
            $table->unsignedBigInteger('owner_id');
            $table->string('status_field', 32)->default('status');
            $table->string('from_status', 64)->nullable();
            $table->string('to_status', 64);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['resource', 'owner_id'], 'domain_transitions_owner_index');
            $table->index(['resource', 'created_at'], 'domain_transitions_resource_date_index');
        });

        /** Restricted document links. Files remain in the private disk. */
        Schema::create('document_links', function (Blueprint $table): void {
            $table->id();
            $table->string('resource', 64);
            $table->unsignedBigInteger('owner_id');
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->string('usage', 48)->default('document');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->unique(['resource', 'owner_id', 'media_id', 'usage'], 'document_links_owner_media_unique');
            $table->index(['resource', 'owner_id'], 'document_links_owner_index');
        });
    }

    private function createGeographyAndOperations(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('code', 64)->unique();
            $table->string('location_type', 48);
            $table->string('status', 32)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'location_type']);
            $table->index('parent_id');
        });

        Schema::create('interventions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('category', 64);
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->unsignedBigInteger('planned_reach')->nullable();
            $table->unsignedBigInteger('verified_reach')->default(0);
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category']);
            $table->index(['program_id', 'project_id']);
        });

        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('intervention_id')->nullable()->constrained('interventions')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('activity_type', 32);
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'activity_type']);
            $table->index(['project_id', 'planned_start']);
        });

        Schema::create('distribution_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('intervention_id')->nullable()->constrained('interventions')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('assistance_type', 64);
            $table->date('planned_date')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('planned');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'assistance_type']);
        });

        Schema::create('distribution_rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained('distribution_plans')->cascadeOnDelete();
            $table->string('code', 64)->unique();
            $table->dateTime('scheduled_at')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('planned');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['plan_id', 'status']);
        });

        Schema::create('distribution_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('round_id')->constrained('distribution_rounds')->cascadeOnDelete();
            $table->string('code', 64)->unique();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->decimal('planned_quantity', 18, 3);
            $table->decimal('recorded_quantity', 18, 3)->nullable();
            $table->decimal('verified_quantity', 18, 3)->nullable();
            $table->string('unit', 32);
            $table->foreignId('evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('reconciliation_notes')->nullable();
            $table->string('status', 32)->default('planned');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['round_id', 'status']);
        });
    }

    private function createInventory(): void
    {
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('capacity_note', 500)->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('category', 64);
            $table->string('unit', 32);
            $table->string('sku', 64)->nullable()->unique();
            $table->decimal('min_stock_threshold', 18, 3)->default(0);
            $table->string('status', 32)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category']);
        });

        Schema::create('inventory_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity', 18, 3)->default(0);
            $table->decimal('reserved_quantity', 18, 3)->default(0);
            $table->decimal('damaged_quantity', 18, 3)->default(0);
            $table->decimal('expired_quantity', 18, 3)->default(0);
            $table->timestamp('updated_at');

            $table->unique(['warehouse_id', 'inventory_item_id'], 'inventory_balance_unique');
        });

        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->string('movement_type', 32);
            $table->string('direction', 16);
            $table->decimal('quantity', 18, 3);
            $table->decimal('before_quantity', 18, 3)->default(0);
            $table->decimal('after_quantity', 18, 3)->default(0);
            $table->string('source_type', 64)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference', 128)->nullable();
            $table->string('reason', 1000)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversal_of')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['warehouse_id', 'inventory_item_id'], 'inventory_movements_balance_index');
            $table->index(['movement_type', 'created_at'], 'inventory_movements_type_date_index');
            $table->index(['source_type', 'source_id'], 'inventory_movements_source_index');
        });
    }

    private function createPeopleAndParticipation(): void
    {
        Schema::create('volunteers', function (Blueprint $table): void {
            $table->id();
            $table->string('internal_reference', 64)->unique();
            $table->foreignId('linked_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->text('skills')->nullable();
            $table->string('availability', 255)->nullable();
            $table->string('status', 32)->default('potential');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('volunteer_teams', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('team_type', 48);
            $table->foreignId('leader_volunteer_id')->nullable()->constrained('volunteers')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->text('skills')->nullable();
            $table->string('status', 32)->default('proposed');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'team_type']);
        });

        Schema::create('volunteer_team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('volunteer_teams')->cascadeOnDelete();
            $table->foreignId('volunteer_id')->constrained('volunteers')->cascadeOnDelete();
            $table->string('member_role', 120)->nullable();
            $table->string('status', 32)->default('active');
            $table->date('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'volunteer_id'], 'volunteer_team_member_unique');
        });

        Schema::create('volunteer_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->constrained('volunteer_teams')->nullOnDelete();
            $table->foreignId('volunteer_id')->nullable()->constrained('volunteers')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignId('distribution_round_id')->nullable()->constrained('distribution_rounds')->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable();
            $table->decimal('hours', 6, 2)->nullable();
            $table->string('status', 32)->default('planned');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('volunteer_trainings', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('provider', 255)->nullable();
            $table->date('training_date')->nullable();
            $table->decimal('hours', 6, 2)->nullable();
            $table->foreignId('evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status', 32)->default('planned');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('volunteer_training', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('volunteer_id')->constrained('volunteers')->cascadeOnDelete();
            $table->foreignId('training_id')->constrained('volunteer_trainings')->cascadeOnDelete();
            $table->date('completion_date')->nullable();
            $table->string('status', 32)->default('planned');
            $table->timestamps();

            $table->unique(['volunteer_id', 'training_id'], 'volunteer_training_unique');
        });

        Schema::create('committees', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('committee_type', 32);
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('status', 32)->default('proposed');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'committee_type']);
        });

        Schema::create('committee_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('committee_id')->constrained('committees')->cascadeOnDelete();
            $table->foreignId('volunteer_id')->nullable()->constrained('volunteers')->nullOnDelete();
            $table->string('internal_reference', 64)->nullable();
            $table->string('member_role', 120)->nullable();
            $table->string('status', 32)->default('active');
            $table->date('joined_at')->nullable();
            $table->timestamps();

            $table->index(['committee_id', 'status']);
        });

        Schema::create('committee_meetings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('committee_id')->constrained('committees')->cascadeOnDelete();
            $table->string('code', 64)->unique();
            $table->date('meeting_date')->nullable();
            $table->text('minutes')->nullable();
            $table->text('decisions')->nullable();
            $table->foreignId('evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status', 32)->default('scheduled');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['committee_id', 'meeting_date']);
        });

        Schema::create('community_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('committee_id')->nullable()->constrained('committees')->nullOnDelete();
            $table->string('category', 64);
            $table->text('summary');
            $table->string('source_reference', 64)->nullable();
            $table->string('status', 32)->default('new');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category']);
        });
    }

    private function createPartnersAndGrants(): void
    {
        Schema::create('partners', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('partner_type', 32);
            $table->string('organization_type', 64);
            $table->string('country', 100)->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('website', 255)->nullable();
            $table->string('public_contact', 255)->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('strategic_alignment')->nullable();
            $table->string('relationship_status', 32)->default('potential');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['relationship_status', 'partner_type']);
        });

        Schema::create('partner_project', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('relationship_type', 64)->nullable();
            $table->timestamps();

            $table->unique(['partner_id', 'project_id'], 'partner_project_unique');
        });

        Schema::create('partner_agreements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('reference', 64)->unique();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('document_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['partner_id', 'status']);
        });

        Schema::create('partner_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->string('internal_reference', 64)->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('role', 120)->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['partner_id', 'status']);
        });

        Schema::create('grant_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->foreignId('funder_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->string('category', 64);
            $table->string('geographic_eligibility', 500)->nullable();
            $table->string('sector_eligibility', 500)->nullable();
            $table->decimal('min_amount', 18, 2)->nullable();
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->string('currency', 3);
            $table->date('opens_at')->nullable();
            $table->date('closes_at')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'closes_at']);
        });

        Schema::create('grant_proposals', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('opportunity_id')->nullable()->constrained('grant_opportunities')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->decimal('budget_amount', 18, 2)->nullable();
            $table->string('currency', 3);
            $table->date('deadline')->nullable();
            $table->date('submitted_at')->nullable();
            $table->foreignId('submission_evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('identified');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'deadline']);
        });

        Schema::create('grant_awards', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->foreignId('donor_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained('grant_proposals')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('payment_schedule_note')->nullable();
            $table->foreignId('agreement_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'donor_partner_id']);
        });
    }

    private function createMealAndResearch(): void
    {
        Schema::create('results_frameworks', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->longText('theory_of_change')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'program_id']);
        });

        Schema::create('reporting_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 32)->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);
        });

        Schema::create('indicators', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('framework_id')->nullable()->constrained('results_frameworks')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('intervention_id')->nullable()->constrained('interventions')->nullOnDelete();
            $table->string('indicator_type', 32);
            $table->string('unit', 64);
            $table->decimal('baseline_value', 18, 4)->nullable();
            $table->decimal('target_value', 18, 4)->nullable();
            $table->text('calculation_rule');
            $table->boolean('disaggregation_enabled')->default(false);
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'indicator_type']);
        });

        Schema::create('indicator_measurements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('indicator_id')->constrained('indicators')->cascadeOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('reporting_periods')->nullOnDelete();
            $table->decimal('reported_value', 18, 4)->nullable();
            $table->decimal('verified_value', 18, 4)->nullable();
            $table->decimal('approved_value', 18, 4)->nullable();
            $table->string('data_source', 500)->nullable();
            $table->foreignId('evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('verification_notes')->nullable();
            $table->date('submitted_at')->nullable();
            $table->date('verified_at')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['indicator_id', 'period_id'], 'measurement_indicator_period_index');
            $table->index(['status', 'verified_at']);
        });

        Schema::create('evaluations', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('evaluation_type', 64);
            $table->foreignId('evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status', 32)->default('planned');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'evaluation_type']);
        });

        Schema::create('corrective_actions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->foreignId('evaluation_id')->nullable()->constrained('evaluations')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status', 32)->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'due_date']);
        });

        Schema::create('assessments', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('assessment_type', 32);
            $table->string('need_category', 64);
            $table->string('methodology_type', 64);
            $table->text('target_population_definition');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('strategic_pillar_id')->nullable()->constrained('strategic_pillars')->nullOnDelete();
            $table->foreignId('evidence_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'assessment_type', 'need_category']);
        });

        Schema::create('data_collection_instruments', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('instrument_type', 64);
            $table->string('version', 32)->nullable();
            $table->boolean('sensitive_data')->default(false);
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'instrument_type']);
        });

        Schema::create('assessment_instrument', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('instrument_id')->constrained('data_collection_instruments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['assessment_id', 'instrument_id'], 'assessment_instrument_unique');
        });
    }

    private function createReporting(): void
    {
        Schema::create('operational_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('category', 64);
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('status_filter', 64)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('summary')->nullable();
            $table->string('format', 16)->default('html');
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_reports');

        Schema::dropIfExists('assessment_instrument');
        Schema::dropIfExists('data_collection_instruments');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('corrective_actions');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('indicator_measurements');
        Schema::dropIfExists('indicators');
        Schema::dropIfExists('reporting_periods');
        Schema::dropIfExists('results_frameworks');

        Schema::dropIfExists('grant_awards');
        Schema::dropIfExists('grant_proposals');
        Schema::dropIfExists('grant_opportunities');
        Schema::dropIfExists('partner_contacts');
        Schema::dropIfExists('partner_agreements');
        Schema::dropIfExists('partner_project');
        Schema::dropIfExists('partners');

        Schema::dropIfExists('community_feedback');
        Schema::dropIfExists('committee_meetings');
        Schema::dropIfExists('committee_members');
        Schema::dropIfExists('committees');
        Schema::dropIfExists('volunteer_training');
        Schema::dropIfExists('volunteer_trainings');
        Schema::dropIfExists('volunteer_assignments');
        Schema::dropIfExists('volunteer_team_members');
        Schema::dropIfExists('volunteer_teams');
        Schema::dropIfExists('volunteers');

        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('warehouses');

        Schema::dropIfExists('distribution_records');
        Schema::dropIfExists('distribution_rounds');
        Schema::dropIfExists('distribution_plans');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('interventions');
        Schema::dropIfExists('locations');

        Schema::dropIfExists('document_links');
        Schema::dropIfExists('domain_transitions');
        Schema::dropIfExists('operational_translations');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn([
                'code', 'project_manager_id', 'responsible_team', 'proposed_budget',
                'approved_budget', 'budget_currency', 'actual_start_date', 'actual_end_date',
            ]);
        });

        Schema::table('programs', function (Blueprint $table): void {
            $table->dropColumn([
                'code', 'program_manager_id', 'responsible_unit', 'category',
                'proposed_budget', 'approved_budget', 'budget_currency',
                'actual_start_date', 'actual_end_date',
            ]);
        });
    }
};
