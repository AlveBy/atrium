<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'create' => 'Dil ekle',
    'fields' => [
        'name' => 'Ad',
        'code' => 'Kod',
        'active' => 'Etkin',
        'is_default' => 'Varsayılan',
        'sort' => 'Sıralama',
    ],
    'hints' => [
        'name' => 'Ziyaretçilerin dil seçicide göreceği ad: English, Deutsch, Español.',
        'code' => 'İki veya üç küçük harf, isteğe bağlı olarak bölgeyle birlikte: en, de, pt-br. adresin öneki olur (/de/…) ve daha sonra değiştirilemez.',
        'code_fixed' => 'Çeviriler bu kodla saklanır ve adresler bu kodla başlar, bu yüzden değiştirilemez.',
        'is_default' => 'Sayfalarının adresinde dil öneki olmaz; çevirisi olmayan yerlerde bu dilin metni gösterilir.',
    ],
    'switch' => 'İçerik dili',
];
