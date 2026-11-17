# Atrium jobs

Vacancies and job applications for [Atrium](https://github.com/AlveBy/atrium): a careers page with a filter, a vacancy page with an application form and schema.org `JobPosting`, CVs on a private disk, and a pipeline in the admin (new → reviewing → interview → offer → hired or rejected) with notes, reply templates and mail to HR. Old applications are deleted after a period you set.

## Install

```bash
composer require alveby/atrium-jobs
php artisan atrium:module:install alveby/atrium-jobs
```

Add `--demo` for departments, offices and vacancies in English, German and Russian. Tell HR about new applications:

```dotenv
ATRIUM_JOBS_NOTIFY=hr@example.com
```

The admin gets Vacancies, Job applications, Departments, Offices and cities, and Reply templates, plus a dashboard tile with the applications at each stage. The site gets `/jobs` and `/jobs/{code}` through `alveby/atrium-theme`.

## On a page

```blade
<x-atrium-jobs::apply-form :vacancy="$vacancy" />
<x-atrium-jobs::apply-form />
```

The first applies for a vacancy, the second sends a CV without one.

## Personal data

`atrium:jobs:prune` deletes applications and their CVs `ATRIUM_JOBS_RETENTION_DAYS` (180) days after their last change; the scheduler runs it daily. The consent checkbox states the period.

Everything else (the CV, permissions, the forms module, the API and MCP, every setting) is in the guide: [docs/jobs.md](https://github.com/AlveBy/atrium/blob/main/docs/jobs.md), in Russian [docs/jobs.ru.md](https://github.com/AlveBy/atrium/blob/main/docs/jobs.ru.md).

## Languages

The admin's 15 languages. English and Russian are written by hand, the rest are machine translations awaiting native review.

## Tests

From the repository root:

```bash
vendor/bin/phpunit packages/jobs/tests
```

## Contributing

This repository is a read-only copy of `packages/jobs` in [AlveBy/atrium](https://github.com/AlveBy/atrium). Issues and pull requests go there.

## License

MIT
