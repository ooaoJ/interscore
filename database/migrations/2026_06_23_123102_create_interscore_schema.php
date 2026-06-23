<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 160)->unique();
            $table->string('email', 160)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->timestamps();
        });

        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained('schools')->cascadeOnDelete();
            $table->boolean('show_student_full_name_public')->default(false);
            $table->boolean('show_student_identifier_public')->default(false);
            $table->boolean('allow_public_history')->default(true);
            $table->boolean('allow_public_team_students')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('name', 150);
            $table->string('email', 180)->unique();
            $table->string('password', 255);
            $table->enum('role', ['platform_admin', 'school_manager', 'moderator']);
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('interclasses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('slug', 180);
            $table->unsignedSmallInteger('year');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->longText('regulation')->nullable();
            $table->string('banner_path', 255)->nullable();
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->enum('status', [
                'draft',
                'registration_open',
                'bracket_generated',
                'in_progress',
                'finished',
                'archived',
                'cancelled'
            ])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'slug']);
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('code', 20)->unique();
            $table->enum('stage', ['elementary_2', 'high_school']);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('competition_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 60)->unique();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('competition_category_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();

            $table->unique(['competition_category_id', 'grade_id'], 'category_grade_unique');
        });

        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->restrictOnDelete();
            $table->string('name', 80);
            $table->enum('shift', ['morning', 'afternoon', 'evening', 'full_time'])->nullable();
            $table->string('course_name', 120)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['interclass_id', 'name']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('display_name', 80)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['M', 'F', 'O', 'N'])->default('N');
            $table->string('school_identifier', 60);
            $table->enum('status', ['active', 'inactive', 'transferred'])->default('active');
            $table->timestamps();

            $table->unique(['interclass_id', 'school_identifier'], 'student_identifier_unique');
        });

        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('modalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained('sports')->restrictOnDelete();
            $table->foreignId('competition_category_id')->constrained('competition_categories')->restrictOnDelete();
            $table->string('name', 160);
            $table->enum('gender', ['male', 'female', 'mixed', 'open'])->default('open');
            $table->enum('team_type', ['collective', 'individual'])->default('collective');
            $table->unsignedSmallInteger('min_athletes')->default(1);
            $table->unsignedSmallInteger('max_athletes')->default(20);
            $table->boolean('allow_draw')->default(false);
            $table->boolean('has_third_place')->default(true);
            $table->enum('bracket_type', ['single_elimination', 'group_stage', 'round_robin'])->default('single_elimination');
            $table->enum('status', ['draft', 'open', 'bracket_generated', 'in_progress', 'finished', 'cancelled'])->default('draft');
            $table->timestamps();

            $table->unique(['interclass_id', 'name']);
        });

        Schema::create('modality_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modality_id')->constrained('modalities')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->decimal('points', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['modality_id', 'position']);
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('modality_id')->constrained('modalities')->cascadeOnDelete();
            $table->string('name', 160);
            $table->enum('status', [
                'draft',
                'valid',
                'blocked',
                'eliminated',
                'champion',
                'runner_up',
                'third_place'
            ])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['modality_id', 'classroom_id']);
        });

        Schema::create('team_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();
            $table->foreignId('modality_id')->constrained('modalities')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('participation_type', ['athlete', 'captain', 'reserve'])->default('athlete');
            $table->timestamps();

            $table->unique(['team_id', 'student_id']);
            $table->unique(['student_id', 'modality_id']);
        });

        Schema::create('moderator_modalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();
            $table->foreignId('modality_id')->constrained('modalities')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'modality_id']);
        });

        Schema::create('brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();
            $table->foreignId('modality_id')->constrained('modalities')->cascadeOnDelete();
            $table->string('name', 160);
            $table->enum('type', ['single_elimination', 'group_stage', 'round_robin'])->default('single_elimination');
            $table->enum('status', ['generated', 'in_progress', 'finished', 'cancelled'])->default('generated');
            $table->string('random_seed', 100)->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('modality_id');
        });

        Schema::create('bracket_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bracket_id')->constrained('brackets')->cascadeOnDelete();
            $table->unsignedInteger('round_number');
            $table->string('name', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['bracket_id', 'round_number']);
        });

        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();
            $table->foreignId('modality_id')->constrained('modalities')->cascadeOnDelete();

            $table->foreignId('bracket_id')->nullable()->constrained('brackets')->nullOnDelete();
            $table->foreignId('round_id')->nullable()->constrained('bracket_rounds')->nullOnDelete();

            $table->unsignedInteger('match_number');

            $table->foreignId('team_a_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('team_b_id')->nullable()->constrained('teams')->nullOnDelete();

            $table->foreignId('next_match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->enum('next_slot', ['A', 'B'])->nullable();

            $table->foreignId('loser_next_match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->enum('loser_next_slot', ['A', 'B'])->nullable();

            $table->dateTime('scheduled_at')->nullable();
            $table->string('location', 160)->nullable();

            $table->integer('score_a')->nullable();
            $table->integer('score_b')->nullable();

            $table->foreignId('winner_team_id')->nullable()->constrained('teams')->nullOnDelete();

            $table->enum('status', [
                'scheduled',
                'in_progress',
                'pending_review',
                'finished',
                'cancelled',
                'wo'
            ])->default('scheduled');

            $table->text('notes')->nullable();

            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['bracket_id', 'match_number']);
        });

        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();
            $table->foreignId('modality_id')->constrained('modalities')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->decimal('points_awarded', 8, 2)->default(0);
            $table->foreignId('defined_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['modality_id', 'position']);
            $table->unique(['modality_id', 'team_id']);
        });

        Schema::create('penalty_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->decimal('default_points', 8, 2)->default(0);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();

            $table->foreignId('modality_id')->nullable()->constrained('modalities')->nullOnDelete();
            $table->foreignId('match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();

            $table->foreignId('penalty_type_id')->constrained('penalty_types')->restrictOnDelete();

            $table->decimal('points', 8, 2);
            $table->text('reason');

            $table->enum('status', ['pending', 'applied', 'cancelled'])->default('applied');

            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('ranking_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('interclass_id')->constrained('interclasses')->cascadeOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->useCurrent();
            $table->text('notes')->nullable();
        });

        Schema::create('ranking_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_snapshot_id')->constrained('ranking_snapshots')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();

            $table->unsignedInteger('position');

            $table->decimal('modality_points', 8, 2)->default(0);
            $table->decimal('penalty_points', 8, 2)->default(0);
            $table->decimal('total_points', 8, 2)->default(0);

            $table->unsignedInteger('first_places')->default(0);
            $table->unsignedInteger('second_places')->default(0);
            $table->unsignedInteger('third_places')->default(0);

            $table->timestamps();

            $table->unique(['ranking_snapshot_id', 'classroom_id'], 'ranking_classroom_unique');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('interclass_id')->nullable()->constrained('interclasses')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 120);
            $table->string('entity_type', 120);
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('ranking_rows');
        Schema::dropIfExists('ranking_snapshots');
        Schema::dropIfExists('penalties');
        Schema::dropIfExists('penalty_types');
        Schema::dropIfExists('placements');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('bracket_rounds');
        Schema::dropIfExists('brackets');
        Schema::dropIfExists('moderator_modalities');
        Schema::dropIfExists('team_students');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('modality_scores');
        Schema::dropIfExists('modalities');
        Schema::dropIfExists('sports');
        Schema::dropIfExists('students');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('competition_category_grades');
        Schema::dropIfExists('competition_categories');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('interclasses');
        Schema::dropIfExists('users');
        Schema::dropIfExists('school_settings');
        Schema::dropIfExists('schools');
    }
};