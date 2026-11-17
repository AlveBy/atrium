{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    The board's styles, on top of the theme's custom properties. Marks
    never rely on colour alone: VIP and highlighted ads carry a word, and
    the badge is --bg on --accent (6.7:1 light, 9.1:1 dark in the theme).
--}}
@push('head')
    @if (! empty($noindex))
    <meta name="robots" content="noindex, follow">
    @endif
    @if (! empty($jsonLd))
    <script type="application/ld+json">{!! $jsonLd !!}</script>
    @endif
    <style>
        .cl-layout { display: grid; gap: 2rem; grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 60rem) { .cl-layout { grid-template-columns: 16rem minmax(0, 1fr); } }
        .cl-filters fieldset { border: 1px solid var(--line); border-radius: .5rem; margin: 0 0 1rem; padding: .75rem 1rem; }
        .cl-filters legend { font-weight: 600; padding: 0 .25rem; }
        .cl-filters label { display: block; margin: .35rem 0; }
        .cl-filters input[type=number], .cl-filters input[type=text], .cl-filters input[type=search], .cl-filters select,
        .cl-form input[type=text], .cl-form input[type=email], .cl-form input[type=tel], .cl-form input[type=number], .cl-form select, .cl-form textarea {
            width: 100%; box-sizing: border-box; padding: .45rem .6rem; border: 1px solid var(--control); border-radius: .4rem; background: var(--bg); color: var(--text); font: inherit;
        }
        .cl-range { display: flex; gap: .5rem; }
        .cl-button { display: inline-block; padding: .5rem 1rem; border: 1px solid var(--accent); border-radius: .4rem; background: var(--accent); color: var(--bg); font: inherit; cursor: pointer; text-decoration: none; }
        .cl-button.secondary { background: transparent; color: var(--accent); }
        .cl-button:focus-visible, .cl-filters :focus-visible, .cl-form :focus-visible { outline: 3px solid var(--focus); outline-offset: 2px; }
        .cl-cards { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); gap: 1.25rem; }
        .cl-card { position: relative; background: var(--surface); border-radius: .6rem; overflow: hidden; display: flex; flex-direction: column; border: 2px solid transparent; }
        .cl-card.is-vip, .cl-card.is-highlighted { border-color: var(--accent); }
        .cl-card img { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; }
        .cl-card .cl-nophoto { aspect-ratio: 4 / 3; background: var(--line); }
        .cl-card-body { padding: .8rem 1rem 1rem; }
        .cl-card h2 { font-size: 1.05rem; margin: 0 0 .3rem; }
        .cl-price { font-weight: 700; font-size: 1.1rem; }
        .cl-meta { color: var(--muted); font-size: .9rem; }
        .cl-badge { display: inline-block; padding: .1rem .5rem; border-radius: .3rem; background: var(--accent); color: var(--bg); font-size: .8rem; font-weight: 700; letter-spacing: .02em; }
        .cl-badges { position: absolute; top: .5rem; left: .5rem; display: flex; gap: .3rem; }
        .cl-subcats { list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: .5rem 1rem; margin: 0 0 1.5rem; }
        .cl-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr)); gap: .5rem; margin: 1rem 0; }
        .cl-gallery img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: .4rem; }
        .cl-gallery a:first-child { grid-column: 1 / -1; }
        .cl-gallery a:first-child img { aspect-ratio: 16 / 9; }
        .cl-ad { display: grid; gap: 2rem; grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 60rem) { .cl-ad { grid-template-columns: minmax(0, 1fr) 20rem; } }
        .cl-box { background: var(--surface); border-radius: .6rem; padding: 1rem 1.2rem; margin-bottom: 1rem; }
        .cl-props { display: grid; grid-template-columns: max-content 1fr; gap: .3rem 1.5rem; margin: 1rem 0; }
        .cl-props dt { color: var(--muted); }
        .cl-props dd { margin: 0; }
        .cl-map { width: 100%; height: 16rem; border: 0; border-radius: .6rem; }
        .cl-form label { display: block; font-weight: 600; margin: 1rem 0 .3rem; }
        .cl-form .hint { color: var(--muted); font-size: .9rem; font-weight: 400; }
        .cl-error { color: var(--text); border-left: 4px solid var(--accent); padding-left: .6rem; margin: .3rem 0; }
        .cl-status { border: 1px solid var(--line); border-left: 4px solid var(--accent); padding: .6rem 1rem; border-radius: .4rem; margin: 0 0 1rem; }
        .cl-table { width: 100%; border-collapse: collapse; }
        .cl-table th, .cl-table td { text-align: left; padding: .6rem .5rem; border-bottom: 1px solid var(--line); vertical-align: top; }
        .cl-actions { display: flex; flex-wrap: wrap; gap: .4rem; }
        .cl-actions form { margin: 0; }
        .cl-thread { list-style: none; padding: 0; }
        .cl-thread li { max-width: 36rem; margin: .5rem 0; padding: .6rem .9rem; border-radius: .6rem; background: var(--surface); }
        .cl-thread li.mine { margin-left: auto; border: 1px solid var(--accent); }
        .cl-nav { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; margin: 0 0 1.5rem; padding: 0; list-style: none; }
        .skip { position: absolute; left: -9999px; }
    </style>
@endpush
