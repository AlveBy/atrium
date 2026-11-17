{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="{{ __('atrium-classifieds::classifieds.pages') }}">
        @if ($url = $paginator->previousPageUrl())
            <a href="{{ $url }}" rel="prev">{{ __('atrium-classifieds::classifieds.previous') }}</a>
        @endif
        <span class="current">{{ __('atrium-classifieds::classifieds.page_of', ['page' => $paginator->currentPage(), 'total' => $paginator->lastPage()]) }}</span>
        @if ($url = $paginator->nextPageUrl())
            <a href="{{ $url }}" rel="next">{{ __('atrium-classifieds::classifieds.next') }}</a>
        @endif
    </nav>
@endif
