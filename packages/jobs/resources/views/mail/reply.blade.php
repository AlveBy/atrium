{{-- #333 on white: 12.63:1. The text is the template's, escaped, with its line breaks. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#333;">
    <div style="max-width:600px;margin:0 auto;padding:20px;">
        <div style="background:#fff;padding:24px;border:1px solid #e5e5e5;border-radius:4px;font-size:15px;line-height:1.5;">
            {!! nl2br(e($body)) !!}
        </div>
    </div>
</body>
</html>
