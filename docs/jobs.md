# Vacancies and job applications

`alveby/atrium-jobs` puts a careers page on an Atrium site and gives HR a small recruiting pipeline in the admin: departments and offices, vacancies with salary and texts, an application form with a CV upload, stages from "new" to "hired", notes, reply templates, mail to HR and automatic deletion of old applications.

[Русская версия](jobs.ru.md)

## Install

```bash
composer require alveby/atrium-jobs
php artisan atrium:module:install alveby/atrium-jobs
```

`atrium:module:install` creates the tables and turns the module on. `atrium:install --demo` (or `atrium:module:install alveby/atrium-jobs --demo`) adds four departments, two offices, four vacancies in English, German and Russian, two reply templates and a few applications from made-up people. `atrium:module:remove alveby/atrium-jobs` turns it off and asks whether to keep the data.

Set who is told about new applications in `.env`:

```dotenv
ATRIUM_JOBS_NOTIFY=hr@example.com,lead@example.com
```

## The admin

| Entry | What it holds |
|---|---|
| Vacancies | title, code, department, office, office/hybrid/remote, employment type, experience, salary from and to with a currency and a period, or "by agreement", short description, about the job, responsibilities, requirements, what we offer, contact person, active, active from and to, sort, SEO |
| Job applications | the candidate's name, e-mail, phone, cover letter, answers, CV, the vacancy, language and page; the stage, notes and a reply |
| Departments | name, sort, on/off |
| Offices and cities | name, city, street, postal code, region, country (two-letter ISO code), sort, on/off |
| Reply templates | subject and text per content language, the stage the application moves to after the letter |

Texts are in every content language. Everything belongs to the current site.

### The pipeline

An application goes through six stages: **New, Reviewing, Interview, Offer, Hired, Rejected**. The admin has no board, so the stages live in two places:

- a dashboard tile with the number of applications at each stage. A click opens the list filtered by that stage (`/admin/job_applications?status=interview`). `?vacancy_id=` filters by vacancy. Turn the tile off with `'dashboard' => false` in the config, or list `Atrium\Jobs\Dashboard\PipelineGadget` in `atrium.dashboard.gadgets` yourself to place it.
- the application form: the stage, notes only HR sees, and **Send a reply**. Pick a template, save, and the letter goes to the candidate in the language they applied in, with `:name`, `:vacancy` and `:company` filled in. The application moves to the template's stage, and the notes record who sent what.

Applications are not created or edited in the admin, apart from the stage, the notes and the reply.

### The CV

The file goes to a private disk (`local`, which is `storage/app/private`). It has no public address. The link in the admin goes through `/admin/job_applications/{id}/cv`, which checks that the admin may see applications (`job_applications.view`) and answers with `Cache-Control: private, no-store`. The CV is not attached to the mail to HR: the mail links to the application.

Allowed types are `pdf`, `doc` and `docx`, up to 5 MB, checked by content and not by the file name. Change them with `cv_types` and `cv_max_size`. `ATRIUM_JOBS_CV_REQUIRED=false` makes the CV optional.

### Permissions

Each entry has the usual four permissions in the role form. Give a recruiter `job_applications.view` and `job_applications.update` to read and move applications, and `vacancies.*` to publish vacancies. An editor without `job_applications.view` sees neither the applications, nor the tile, nor the CVs.

## The site

The theme draws the pages:

- `/jobs`: open vacancies with a filter by department, office, employment type and work format. The filter offers only values some open vacancy has. With no open vacancies the page says so and offers the general form.
- `/jobs/{code}`: the vacancy with its details, salary, texts, contact person and the application form, plus schema.org `JobPosting` in `<head>`.

Both work in every content language (`/de/jobs/…`) and the vacancies go into `sitemap.xml`. A vacancy is open while it is active and today is within its active-from and active-to dates. A closed vacancy answers 404 and refuses applications.

### The form anywhere

```blade
<x-atrium-jobs::apply-form :vacancy="$vacancy" />
<x-atrium-jobs::apply-form />
```

The first applies for one vacancy, the second sends a CV without one. Both have a name, an e-mail, a phone, a cover letter, the CV and a consent checkbox. The form posts to `/jobs/apply` (`apply_path`) and comes back to the same page, scrolled to the form, with the thank-you or the errors. A request that wants JSON gets `{"message": "…"}` or a 422 with `errors`.

The markup takes the colours of `alveby/atrium-theme` in light and dark mode. The error text is `#b91c1c` on white (6.5:1) and `#fca5a5` on the dark background (9.7:1), and a field with an error also gets a text message, not only a colour. To change the pages or the form:

```bash
php artisan vendor:publish --tag=atrium-jobs-views
```

### JobPosting

The structured data comes from the vacancy: title, description (all its texts), dates, employment type, the office's address (`addressCountry` from the country code), `TELECOMMUTE` for remote work, the salary as `MonetaryAmount` with `HOUR`, `MONTH` or `YEAR`, the experience, and the site's name as the hiring organization. Check a page with Google's Rich Results Test before relying on it.

## Spam and limits

- A hidden field (`honeypot`) people never see. A form with it filled is answered as sent and dropped.
- The time the form was drawn, encrypted with the app key. A form sent sooner than `min_seconds` (3) is refused.
- At most `per_hour` (10) applications from one IP address per hour.

## With the forms module

When `alveby/atrium-forms` is installed and the site has a form with the code `job-application` (`form` in the config), its fields are asked after the standard ones. The answers are kept with the application, a copy goes to the form's submissions, and the form's recipients get the mail too. Without the module or the form, the standard fields are all there is, and the application is stored in the module's own table either way. Deleting an application deletes its copy.

## Personal data

An application holds personal data, so it does not live forever. `atrium:jobs:prune` deletes applications, their CV files and their copies in the forms module `retention_days` (180) days after the application last changed (a stage, a note, a reply). The scheduler runs it daily, so the server needs the usual cron line:

```cron
* * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
```

```bash
php artisan atrium:jobs:prune            # with retention_days
php artisan atrium:jobs:prune --days=90  # once, with another period
```

`ATRIUM_JOBS_RETENTION_DAYS=0` keeps applications until they are deleted by hand. The consent text tells the candidate the period. Link it to your privacy policy with `ATRIUM_JOBS_PRIVACY_URL`. What the module keeps besides the form: the IP address and browser (for spam), the page and the language.

## API and AI assistants

Vacancies, departments, offices, templates and applications are in the REST API (`/api/v1/vacancies`, `/api/v1/job_applications`, …) and in the MCP tools like every other module. Both need a token and the rights of its owner (see [Configuration](configuration.md)). An assistant can publish a vacancy with a token that has `vacancies:write` and an owner with `vacancies.create`. It can read applications only with `job_applications:read` and an owner with `job_applications.view`. Applications cannot be created through the API: they come from the form.

## Configuration

```bash
php artisan vendor:publish --tag=atrium-jobs-config
```

| Key | `.env` | Default | |
|---|---|---|---|
| `apply_path` | | `jobs/apply` | where the form posts |
| `notify` | `ATRIUM_JOBS_NOTIFY` | empty | HR addresses, comma-separated. The vacancy's contact e-mail is added |
| `disk` | `ATRIUM_JOBS_DISK` | `local` | a private disk for CVs |
| `cv_required` | `ATRIUM_JOBS_CV_REQUIRED` | `true` | |
| `cv_types` | | `pdf, doc, docx` | |
| `cv_max_size` | `ATRIUM_JOBS_CV_MAX_SIZE` | `5120` | kilobytes |
| `retention_days` | `ATRIUM_JOBS_RETENTION_DAYS` | `180` | `0` keeps applications forever |
| `privacy_url` | `ATRIUM_JOBS_PRIVACY_URL` | none | linked from the consent checkbox |
| `form` | | `job-application` | the forms module's form with extra questions |
| `honeypot` | | `website` | |
| `min_seconds` | `ATRIUM_JOBS_MIN_SECONDS` | `3` | |
| `per_hour` | `ATRIUM_JOBS_PER_HOUR` | `10` | per IP address |
| `per_page` | | `20` | vacancies per page |
| `currencies` | | EUR, USD, GBP, PLN, CHF, RUB, BYN, UAH, KZT | any ISO 4217 code works |
| `dashboard` | | `true` | the pipeline tile |
