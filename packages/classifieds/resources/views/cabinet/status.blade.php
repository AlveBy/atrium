{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@if (session('atrium-classifieds.status'))
    <p class="cl-status" role="status">{{ session('atrium-classifieds.status') }}</p>
@endif
@if ($errors->any())
    <div class="cl-status" role="alert">
        @foreach ($errors->all() as $error)
            <p class="cl-error">{{ $error }}</p>
        @endforeach
    </div>
@endif
