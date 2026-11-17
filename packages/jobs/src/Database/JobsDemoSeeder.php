<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Database;

use Atrium\Admin\Modules\DemoSeeder;
use Atrium\Admin\Support\Sites;
use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Models\Department;
use Atrium\Jobs\Models\Location;
use Atrium\Jobs\Models\ReplyTemplate;
use Atrium\Jobs\Models\Vacancy;
use Illuminate\Database\Eloquent\Model;

/**
 * A made-up company of the install's country hiring in two of its cities
 * and remotely: departments, offices, four vacancies, two reply templates
 * and a few applications to show the pipeline.
 *
 * The main language (APP_LOCALE, or the one given) picks the country: a
 * German install gets a company in Germany with euros a year, a Japanese
 * one a company in Japan with yen. The texts are there in all fifteen
 * languages and show once a language is on.
 */
final class JobsDemoSeeder extends DemoSeeder
{
    /**
     * Each vacancy: its department, office (0 or 1), and how it is worked.
     *
     * @var array<string,array{department:string,office:int,work_mode:string,employment_type:string,experience:string}>
     */
    private const VACANCIES = [
        'backend' => ['department' => 'engineering', 'office' => 0, 'work_mode' => 'hybrid', 'employment_type' => 'full_time', 'experience' => '3-6'],
        'designer' => ['department' => 'design', 'office' => 1, 'work_mode' => 'remote', 'employment_type' => 'full_time', 'experience' => '1-3'],
        'account' => ['department' => 'sales', 'office' => 0, 'work_mode' => 'office', 'employment_type' => 'full_time', 'experience' => '1-3'],
        'intern' => ['department' => 'support', 'office' => 1, 'work_mode' => 'office', 'employment_type' => 'internship', 'experience' => 'none'],
    ];

    /** Who applied for what: a vacancy key, or null for a CV without one. */
    private const APPLICATIONS = [['backend', 'new'], ['backend', 'interview'], ['designer', 'reviewing'], [null, 'new']];

    public function __construct(private ?string $locale = null) {}

    public function run(): bool
    {
        if (Vacancy::query()->withoutGlobalScopes()->exists()) {
            return false;
        }

        $site = app(Sites::class)->default()->id;
        $main = DemoContent::locale($this->locale ?? (string) config('app.locale'));
        $country = DemoContent::country($main);
        $texts = $this->texts($main);
        $email = fn (string $user): string => $user.'@'.$country['domain'];

        $departments = [];

        foreach (array_keys($texts[$main]['departments']) as $index => $key) {
            $departments[$key] = $this->translated(new Department(['site_id' => $site, 'sort' => 100 * ($index + 1)]), [
                'name' => array_map(fn (array $language): string => $language['departments'][$key], $texts),
            ]);
        }

        $offices = [];

        foreach ($country['offices'] as $index => $office) {
            // A city keeps the name it has in its own country in every language.
            $offices[] = $this->translated(new Location([
                'site_id' => $site,
                'street' => $office['street'],
                'postal_code' => $office['postal_code'],
                'region' => $office['region'],
                'country' => $country['code'],
                'sort' => 100 * ($index + 1),
            ]), ['name' => array_fill_keys(array_keys($texts), $office['name'])]);
        }

        $vacancies = [];
        $index = 0;

        foreach (self::VACANCIES as $key => $vacancy) {
            $salary = $country['salaries'][$key] ?? null;

            $vacancies[$key] = $this->translated(new Vacancy([
                'site_id' => $site,
                'active' => true,
                'active_from' => now()->subDays(3 + $index * 4),
                'active_to' => now()->addDays(60),
                'sort' => 100 * ++$index,
                'department_id' => $departments[$vacancy['department']]->id,
                'location_id' => $offices[$vacancy['office']]->id,
                'work_mode' => $vacancy['work_mode'],
                'employment_type' => $vacancy['employment_type'],
                'experience' => $vacancy['experience'],
                'salary_negotiable' => $salary === null,
                'salary_from' => $salary['from'] ?? null,
                'salary_to' => $salary['to'] ?? null,
                'salary_currency' => $salary === null ? null : $country['currency'],
                'salary_period' => $salary['period'] ?? 'month',
                'contact_name' => $country['contact']['name'],
                'contact_email' => $email($country['contact']['email']),
                'contact_phone' => $country['contact']['phone'],
            ]), array_map(
                fn (string $field): array => array_map(fn (array $language): string => $language['vacancies'][$key][$field], $texts),
                array_combine($fields = ['name', 'code', 'preview_text', 'description', 'duties', 'requirements', 'conditions'], $fields),
            ));
        }

        foreach (['interview' => 'interview', 'rejection' => 'rejected'] as $key => $status) {
            $this->translated(new ReplyTemplate([
                'site_id' => $site,
                'name' => $texts[$main]['templates'][$key]['name'],
                'status' => $status,
                'sort' => $status === 'interview' ? 100 : 200,
            ]), [
                'subject' => array_map(fn (array $language): string => $language['templates'][$key]['subject'], $texts),
                'body' => array_map(fn (array $language): string => $language['templates'][$key]['body'], $texts),
            ]);
        }

        foreach (self::APPLICATIONS as $i => [$key, $status]) {
            $vacancy = $key === null ? null : $vacancies[$key];
            $applicant = $country['applicants'][$i];

            $application = new Application;
            $application->forceFill([
                'site_id' => $site,
                'vacancy_id' => $vacancy?->id,
                'vacancy_name' => $vacancy?->getTranslation('name', DemoContent::language($main)),
                'status' => $status,
                'name' => $applicant['name'],
                'email' => $email($applicant['email']),
                'message' => $texts[$main]['messages'][$i],
                'consent_at' => now(),
                'locale' => DemoContent::language($main),
            ])->save();
        }

        return true;
    }

    /**
     * The texts of every language, keyed by content language code, the
     * main language first.
     *
     * @return array<string,array{departments:array<string,string>,vacancies:array<string,array<string,string>>,templates:array<string,array{name:string,subject:string,body:string}>,messages:array<int,string>}>
     */
    private function texts(string $main): array
    {
        $texts = [];

        foreach (array_unique([$main, ...DemoContent::LOCALES]) as $locale) {
            $texts[DemoContent::language($locale)] = DemoContent::texts($locale);
        }

        return $texts;
    }

    /**
     * @template T of Department|Location|Vacancy|ReplyTemplate
     *
     * @param  T  $model
     * @param  array<string,array<string,string>>  $texts  attribute => language => text
     * @return T
     */
    private function translated(Model $model, array $texts): Model
    {
        foreach ($texts as $attribute => $languages) {
            foreach ($languages as $language => $text) {
                $model->setTranslation($attribute, $language, $text);
            }
        }

        $model->save();

        return $model;
    }
}
