<?php

declare(strict_types=1);

namespace Atrium\Jobs\Database;

use Atrium\Admin\Modules\DemoSeeder;
use Atrium\Admin\Support\Sites;
use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Models\Department;
use Atrium\Jobs\Models\Location;
use Atrium\Jobs\Models\ReplyTemplate;
use Atrium\Jobs\Models\Vacancy;

/**
 * A made-up company hiring in two offices and remotely: departments,
 * offices, four vacancies in English, German and Russian, two reply
 * templates and a few applications to show the pipeline. The German and
 * Russian texts show once those languages are on (the News demo adds them).
 */
final class JobsDemoSeeder extends DemoSeeder
{
    public function run(): bool
    {
        if (Vacancy::query()->withoutGlobalScopes()->exists()) {
            return false;
        }

        $site = app(Sites::class)->default()->id;

        $departments = [];

        foreach ([
            'engineering' => ['en' => 'Engineering', 'de' => 'Entwicklung', 'ru' => 'Разработка'],
            'design' => ['en' => 'Design', 'de' => 'Design', 'ru' => 'Дизайн'],
            'sales' => ['en' => 'Sales', 'de' => 'Vertrieb', 'ru' => 'Продажи'],
            'support' => ['en' => 'Customer support', 'de' => 'Kundenservice', 'ru' => 'Поддержка клиентов'],
        ] as $key => $names) {
            $departments[$key] = $this->translated(new Department(['site_id' => $site, 'sort' => 100 * (count($departments) + 1)]), ['name' => $names]);
        }

        $locations = [
            'berlin' => $this->translated(new Location([
                'site_id' => $site, 'street' => 'Beispielstraße 12', 'postal_code' => '10115', 'country' => 'DE', 'sort' => 100,
            ]), ['name' => ['en' => 'Berlin', 'de' => 'Berlin', 'ru' => 'Берлин']]),
            'warsaw' => $this->translated(new Location([
                'site_id' => $site, 'street' => 'ul. Przykładowa 7', 'postal_code' => '00-001', 'country' => 'PL', 'sort' => 200,
            ]), ['name' => ['en' => 'Warsaw', 'de' => 'Warschau', 'ru' => 'Варшава']]),
        ];

        $vacancies = [];

        foreach ($this->vacancies() as $index => $data) {
            $vacancy = new Vacancy([
                'site_id' => $site,
                'active' => true,
                'active_from' => now()->subDays(3 + $index * 4),
                'active_to' => now()->addDays(60),
                'sort' => 100 * ($index + 1),
                'department_id' => $departments[$data['department']]->id,
                'location_id' => $locations[$data['location']]->id,
                ...$data['attributes'],
                'contact_name' => 'Alex Example',
                'contact_email' => 'jobs@example.com',
            ]);

            $vacancies[] = $this->translated($vacancy, $data['texts']);
        }

        $this->translated(new ReplyTemplate(['site_id' => $site, 'name' => 'Interview invitation', 'status' => 'interview', 'sort' => 100]), [
            'subject' => [
                'en' => 'Your application for :vacancy',
                'de' => 'Ihre Bewerbung als :vacancy',
                'ru' => 'Ваш отклик на вакансию «:vacancy»',
            ],
            'body' => [
                'en' => "Hello :name,\n\nthank you for applying for :vacancy. We would like to meet you: please reply with two or three times that suit you next week.\n\nBest regards,\n:company",
                'de' => "Hallo :name,\n\nvielen Dank für Ihre Bewerbung als :vacancy. Wir würden Sie gern kennenlernen: Nennen Sie uns bitte zwei oder drei passende Termine in der nächsten Woche.\n\nViele Grüße\n:company",
                'ru' => "Здравствуйте, :name!\n\nСпасибо за отклик на вакансию «:vacancy». Мы хотим познакомиться: напишите, пожалуйста, два-три удобных времени на следующей неделе.\n\nС уважением,\n:company",
            ],
        ]);

        $this->translated(new ReplyTemplate(['site_id' => $site, 'name' => 'Rejection', 'status' => 'rejected', 'sort' => 200]), [
            'subject' => [
                'en' => 'Your application for :vacancy',
                'de' => 'Ihre Bewerbung als :vacancy',
                'ru' => 'Ваш отклик на вакансию «:vacancy»',
            ],
            'body' => [
                'en' => "Hello :name,\n\nthank you for your interest in :vacancy. We have decided to continue with other candidates this time. We will keep your CV and contact you if a suitable position opens.\n\nBest regards,\n:company",
                'de' => "Hallo :name,\n\nvielen Dank für Ihr Interesse an der Stelle als :vacancy. Diesmal haben wir uns für andere Kandidaten entschieden. Wir behalten Ihren Lebenslauf und melden uns, wenn eine passende Stelle frei wird.\n\nViele Grüße\n:company",
                'ru' => "Здравствуйте, :name!\n\nСпасибо за интерес к вакансии «:vacancy». В этот раз мы выбрали других кандидатов. Мы сохраним ваше резюме и напишем, если откроется подходящая позиция.\n\nС уважением,\n:company",
            ],
        ]);

        foreach ([
            [$vacancies[0], 'Jamie Sample', 'jamie@example.com', 'new', 'Hi! I have five years of Laravel behind me and would love to join the team.'],
            [$vacancies[0], 'Robin Muster', 'robin@example.org', 'interview', 'Ich arbeite seit sechs Jahren mit PHP und PostgreSQL.'],
            [$vacancies[1], 'Мария Пример', 'maria@example.net', 'reviewing', 'Добрый день! Портфолио — по ссылке в резюме.'],
            [null, 'Sam Placeholder', 'sam@example.com', 'new', 'No vacancy fits yet, but I would like to work with you as a QA engineer.'],
        ] as [$vacancy, $name, $email, $status, $message]) {
            $application = new Application;
            $application->forceFill([
                'site_id' => $site,
                'vacancy_id' => $vacancy?->id,
                'vacancy_name' => $vacancy?->getTranslation('name', 'en'),
                'status' => $status,
                'name' => $name,
                'email' => $email,
                'message' => $message,
                'consent_at' => now(),
                'locale' => 'en',
            ])->save();
        }

        return true;
    }

    /**
     * @template T of Department|Location|Vacancy|ReplyTemplate
     *
     * @param  T  $model
     * @param  array<string,array<string,string>>  $texts  attribute => language => text
     * @return T
     */
    private function translated($model, array $texts)
    {
        foreach ($texts as $attribute => $languages) {
            foreach ($languages as $language => $text) {
                $model->setTranslation($attribute, $language, $text);
            }
        }

        $model->save();

        return $model;
    }

    /**
     * @return array<int,array{department:string,location:string,attributes:array<string,mixed>,texts:array<string,array<string,string>>}>
     */
    private function vacancies(): array
    {
        return [
            [
                'department' => 'engineering',
                'location' => 'berlin',
                'attributes' => [
                    'work_mode' => 'hybrid', 'employment_type' => 'full_time', 'experience' => '3-6',
                    'salary_from' => 4500, 'salary_to' => 6000, 'salary_currency' => 'EUR', 'salary_period' => 'month',
                ],
                'texts' => [
                    'name' => ['en' => 'Backend developer (PHP, Laravel)', 'de' => 'Backend-Entwickler (PHP, Laravel) (m/w/d)', 'ru' => 'Бэкенд-разработчик (PHP, Laravel)'],
                    'code' => ['en' => 'backend-developer', 'de' => 'backend-entwickler', 'ru' => 'backend-razrabotchik'],
                    'preview_text' => [
                        'en' => 'Build the services behind our product with a small team that ships every week.',
                        'de' => 'Entwickeln Sie die Dienste hinter unserem Produkt in einem kleinen Team, das jede Woche ausliefert.',
                        'ru' => 'Делайте сервисы нашего продукта в небольшой команде, которая выпускает релизы каждую неделю.',
                    ],
                    'description' => [
                        'en' => '<p>We are a team of eight developers. You will own features from the database to the API.</p>',
                        'de' => '<p>Wir sind ein Team aus acht Entwicklern. Sie verantworten Funktionen von der Datenbank bis zur API.</p>',
                        'ru' => '<p>Нас восемь разработчиков. Вы будете вести задачи целиком — от базы данных до API.</p>',
                    ],
                    'duties' => [
                        'en' => '<ul><li>Design and build REST APIs</li><li>Review code of colleagues</li><li>Keep the test suite green</li></ul>',
                        'de' => '<ul><li>REST-APIs entwerfen und umsetzen</li><li>Code von Kollegen prüfen</li><li>Die Tests grün halten</li></ul>',
                        'ru' => '<ul><li>Проектировать и писать REST API</li><li>Проводить ревью кода коллег</li><li>Следить, чтобы тесты оставались зелёными</li></ul>',
                    ],
                    'requirements' => [
                        'en' => '<ul><li>3+ years of PHP</li><li>Laravel, SQL, Git</li><li>English at B2 level</li></ul>',
                        'de' => '<ul><li>Mindestens 3 Jahre PHP</li><li>Laravel, SQL, Git</li><li>Englisch auf B2-Niveau</li></ul>',
                        'ru' => '<ul><li>От 3 лет на PHP</li><li>Laravel, SQL, Git</li><li>Английский на уровне B2</li></ul>',
                    ],
                    'conditions' => [
                        'en' => '<ul><li>Two office days a week</li><li>30 days of holiday</li><li>Learning budget</li></ul>',
                        'de' => '<ul><li>Zwei Bürotage pro Woche</li><li>30 Urlaubstage</li><li>Weiterbildungsbudget</li></ul>',
                        'ru' => '<ul><li>Два дня в неделю в офисе</li><li>30 дней отпуска</li><li>Бюджет на обучение</li></ul>',
                    ],
                ],
            ],
            [
                'department' => 'design',
                'location' => 'warsaw',
                'attributes' => [
                    'work_mode' => 'remote', 'employment_type' => 'full_time', 'experience' => '1-3',
                    'salary_from' => 12000, 'salary_to' => 16000, 'salary_currency' => 'PLN', 'salary_period' => 'month',
                ],
                'texts' => [
                    'name' => ['en' => 'Product designer', 'de' => 'Produktdesigner (m/w/d)', 'ru' => 'Продуктовый дизайнер'],
                    'code' => ['en' => 'product-designer', 'de' => 'produktdesigner', 'ru' => 'produktovyj-dizajner'],
                    'preview_text' => [
                        'en' => 'Shape how customers use our app, from first sketches to the release.',
                        'de' => 'Gestalten Sie, wie Kunden unsere App nutzen – von der ersten Skizze bis zum Release.',
                        'ru' => 'Определяйте, как клиенты пользуются нашим приложением, — от первых набросков до релиза.',
                    ],
                    'duties' => [
                        'en' => '<ul><li>Interview users and test prototypes</li><li>Keep the design system in order</li></ul>',
                        'de' => '<ul><li>Nutzer befragen und Prototypen testen</li><li>Das Designsystem pflegen</li></ul>',
                        'ru' => '<ul><li>Проводить интервью с пользователями и тестировать прототипы</li><li>Поддерживать дизайн-систему</li></ul>',
                    ],
                    'requirements' => [
                        'en' => '<ul><li>A portfolio with shipped products</li><li>Figma</li></ul>',
                        'de' => '<ul><li>Ein Portfolio mit veröffentlichten Produkten</li><li>Figma</li></ul>',
                        'ru' => '<ul><li>Портфолио с вышедшими продуктами</li><li>Figma</li></ul>',
                    ],
                ],
            ],
            [
                'department' => 'sales',
                'location' => 'berlin',
                'attributes' => [
                    'work_mode' => 'office', 'employment_type' => 'full_time', 'experience' => '1-3',
                    'salary_negotiable' => true,
                ],
                'texts' => [
                    'name' => ['en' => 'Account manager', 'de' => 'Account Manager (m/w/d)', 'ru' => 'Менеджер по работе с клиентами'],
                    'code' => ['en' => 'account-manager', 'de' => 'account-manager', 'ru' => 'menedzher-po-rabote-s-klientami'],
                    'preview_text' => [
                        'en' => 'Look after our business customers and help them get more out of the product.',
                        'de' => 'Betreuen Sie unsere Geschäftskunden und helfen Sie ihnen, mehr aus dem Produkt zu holen.',
                        'ru' => 'Ведите корпоративных клиентов и помогайте им получать от продукта больше.',
                    ],
                    'description' => [
                        'en' => '<p>You will have about thirty customers of your own and a team that backs you up.</p>',
                        'de' => '<p>Sie betreuen rund dreißig eigene Kunden, mit einem Team im Rücken.</p>',
                        'ru' => '<p>У вас будет около тридцати своих клиентов и команда, которая поддержит.</p>',
                    ],
                ],
            ],
            [
                'department' => 'support',
                'location' => 'warsaw',
                'attributes' => [
                    'work_mode' => 'office', 'employment_type' => 'internship', 'experience' => 'none',
                    'salary_from' => 30, 'salary_currency' => 'PLN', 'salary_period' => 'hour',
                ],
                'texts' => [
                    'name' => ['en' => 'Customer support intern', 'de' => 'Praktikum im Kundenservice', 'ru' => 'Стажёр службы поддержки'],
                    'code' => ['en' => 'support-intern', 'de' => 'praktikum-kundenservice', 'ru' => 'stazher-podderzhki'],
                    'preview_text' => [
                        'en' => 'Three months, twenty hours a week, with a mentor: a good start in IT.',
                        'de' => 'Drei Monate, zwanzig Stunden pro Woche, mit Mentor: ein guter Einstieg in die IT.',
                        'ru' => 'Три месяца, двадцать часов в неделю, с наставником — хороший старт в IT.',
                    ],
                    'conditions' => [
                        'en' => '<ul><li>Flexible hours around your studies</li><li>A job offer for the best interns</li></ul>',
                        'de' => '<ul><li>Flexible Zeiten neben dem Studium</li><li>Ein Jobangebot für die Besten</li></ul>',
                        'ru' => '<ul><li>Гибкий график, удобный для учёбы</li><li>Лучшим стажёрам — предложение о работе</li></ul>',
                    ],
                ],
            ],
        ];
    }
}
