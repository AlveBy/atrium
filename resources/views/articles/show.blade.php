<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $article->name }}</title>
    <x-atrium::hreflang :urls="$urls" />
    <x-atrium::head />
</head>
<body>
    <x-atrium::body-start />
    @if (count($urls) > 1)
        <nav aria-label="Language">
            @foreach ($urls as $code => $url)
                <a href="{{ $url }}" hreflang="{{ $code }}" @if ($code === app()->getLocale()) aria-current="page" @endif>{{ app(\Atrium\Admin\Support\ContentLanguages::class)->find($code)?->name }}</a>
            @endforeach
        </nav>
    @endif

    <article>
        <h1>{{ $article->name }}</h1>
        {!! $article->detail_text ?: $article->preview_text !!}
    </article>

    <x-atrium::body-end />
</body>
</html>
