{{-- Colours: white on #2563eb 5.17:1, #333 on white 12.63:1, #555 on white 7.46:1. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $application->vacancy_name ?? __('atrium-jobs::jobs.public.general') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#333;">
    <div style="max-width:600px;margin:0 auto;padding:20px;">
        <div style="background:#2563eb;color:#fff;padding:18px 24px;border-radius:4px 4px 0 0;">
            <h1 style="margin:0;font-size:18px;">{{ __('atrium-jobs::jobs.mail.heading', ['vacancy' => $application->vacancy_name ?? __('atrium-jobs::jobs.public.general')]) }}</h1>
            <div style="margin-top:4px;font-size:14px;">{{ $application->submitted_at }}</div>
        </div>
        <div style="background:#fff;padding:24px;border:1px solid #e5e5e5;border-top:none;border-radius:0 0 4px 4px;">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                @foreach ([
                    __('atrium-jobs::jobs.fields.name') => $application->name,
                    __('atrium-jobs::jobs.fields.email') => $application->email,
                    __('atrium-jobs::jobs.fields.phone') => $application->phone,
                    __('atrium-jobs::jobs.fields.message') => $application->message,
                    __('atrium-jobs::jobs.fields.cv') => $application->cv_label ?? __('atrium-jobs::jobs.public.no_cv'),
                ] as $label => $value)
                    @continue($value === null || $value === '')
                    <tr>
                        <th style="text-align:left;vertical-align:top;padding:6px 12px 6px 0;color:#555;font-weight:normal;width:35%;">{{ $label }}</th>
                        <td style="padding:6px 0;white-space:pre-wrap;">{{ $value }}</td>
                    </tr>
                @endforeach
                @foreach ($application->answers ?? [] as $item)
                    <tr>
                        <th style="text-align:left;vertical-align:top;padding:6px 12px 6px 0;color:#555;font-weight:normal;width:35%;">{{ $item['label'] }}</th>
                        <td style="padding:6px 0;white-space:pre-wrap;">{{ \Atrium\Jobs\Models\Application::text($item['value'] ?? null) }}</td>
                    </tr>
                @endforeach
            </table>

            @if ($adminUrl)
                <p style="margin:20px 0 0;">
                    <a href="{{ $adminUrl }}" style="display:inline-block;background:#2563eb;color:#fff;padding:10px 18px;border-radius:4px;text-decoration:none;">{{ __('atrium-jobs::jobs.mail.open') }}</a>
                </p>
            @endif

            <p style="margin:18px 0 0;font-size:13px;color:#555;">{{ __('atrium-jobs::jobs.mail.cv_note') }}</p>
        </div>
    </div>
</body>
</html>
