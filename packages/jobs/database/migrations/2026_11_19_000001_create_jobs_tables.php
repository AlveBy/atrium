<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_departments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->json('name');
            $table->boolean('active')->default(true);
            $table->integer('sort')->default(500);
            $table->timestamps();
        });

        // A city or an office: where the work is done.
        Schema::create('job_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->json('name');
            $table->string('street')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('region')->nullable();
            $table->string('country', 2)->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort')->default(500);
            $table->timestamps();
        });

        Schema::create('vacancies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->boolean('active')->default(true);
            $table->dateTime('active_from')->nullable();
            $table->dateTime('active_to')->nullable();
            $table->integer('sort')->default(500);
            $table->json('name');
            $table->json('code');
            $table->foreignId('department_id')->nullable()->constrained('job_departments')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('job_locations')->nullOnDelete();
            $table->string('work_mode', 10)->default('office');
            $table->string('employment_type', 20)->default('full_time');
            $table->string('experience', 20)->nullable();
            $table->boolean('salary_negotiable')->default(false);
            $table->decimal('salary_from', 12, 2)->nullable();
            $table->decimal('salary_to', 12, 2)->nullable();
            $table->string('salary_currency', 3)->nullable();
            $table->string('salary_period', 10)->default('month');
            $table->json('preview_text')->nullable();
            $table->json('description')->nullable();
            $table->json('duties')->nullable();
            $table->json('requirements')->nullable();
            $table->json('conditions')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->timestamps();

            $table->index(['active', 'sort']);
        });

        // What HR writes to a candidate: an invitation, a refusal.
        Schema::create('job_reply_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->string('name');
            $table->json('subject');
            $table->json('body');
            $table->string('status', 20)->nullable();
            $table->integer('sort')->default(500);
            $table->timestamps();
        });

        // An application outlives its vacancy: the title is copied. The CV
        // lies on a private disk; the row and the file go together.
        Schema::create('job_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->index();
            $table->foreignId('vacancy_id')->nullable()->constrained('vacancies')->nullOnDelete();
            $table->string('vacancy_name')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->text('message')->nullable();
            $table->json('answers')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('cv_name')->nullable();
            $table->unsignedInteger('cv_size')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('consent_at')->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('page_url', 2048)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            // A copy in the forms module's submissions, when there is one.
            $table->unsignedBigInteger('form_submission_id')->nullable();
            $table->timestamps();

            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_reply_templates');
        Schema::dropIfExists('vacancies');
        Schema::dropIfExists('job_locations');
        Schema::dropIfExists('job_departments');
    }
};
