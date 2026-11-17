<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// The classifieds demo in English. Every ad is made up. The order of the
// ads matters: ClassifiedsDemoSeeder gives each one its category, city,
// price and status by its place in the list.
return [
    'categories' => [
        'transport' => 'Transport',
        'cars' => 'Cars',
        'bicycles' => 'Bicycles',
        'property' => 'Property',
        'rent' => 'Flats to rent',
        'sale' => 'Houses for sale',
        'electronics' => 'Electronics',
        'phones' => 'Phones',
        'computers' => 'Computers',
        'home' => 'Home and garden',
        'furniture' => 'Furniture',
        'services' => 'Services',
    ],
    'ads' => [
        ['Compact hatchback, 2016, one owner', "Serviced every year at the same garage, all papers kept. Winter tyres on rims come with it.\n\nNo accidents, a small scratch on the rear bumper."],
        ['Family estate car, automatic', "Roomy boot, roof rails, parking sensors front and back. Recently had new brake pads and discs."],
        ['City bike with a basket', "Seven gears, dynamo lights, mudguards. Fits riders from 160 to 180 cm."],
        ['Mountain bike, 29-inch wheels', "Hydraulic disc brakes, front suspension with lockout. Ridden for two summers, chain replaced in spring."],
        ['Bright one-bedroom flat near the park', "Fourth floor of five, windows to the courtyard. Furnished kitchen, washing machine, fast internet. Long term only."],
        ['Studio for students, all bills included', "Ten minutes on foot to the university. Desk, wardrobe and a new sofa bed."],
        ['Brick house with a garden', "Three bedrooms, a garage and a greenhouse. Quiet street, school and shop within walking distance."],
        ['Smartphone, 128 GB, with a case', "Two years old, battery holds a full day. Screen protector from day one, no scratches."],
        ['Push-button phone for an older relative', "Big keys, loud speaker, an SOS button. Charging stand included."],
        ['Laptop for study and office work', "14-inch screen, 16 GB of memory, a fresh battery. Comes with a charger and a sleeve."],
        ['Gaming desktop, ready to play', "Quiet case, liquid cooling, two monitors' worth of outputs. Keyboard and mouse included."],
        ['Solid oak dining table and four chairs', "The table extends to seat eight. Some marks on the top, the chairs were reupholstered last year."],
        ['Corner sofa, grey, washable covers', "Pet-free and smoke-free home. Collection only, it comes apart into three pieces."],
        ['Bookcase, white, 180 cm', "Five shelves, adjustable. Assembled once, never moved."],
        ['Bathroom and kitchen tiling', "Twenty years of experience, clean work, fixed price after a visit. Photos of finished jobs on request."],
        ['Moving help with a van, two loaders', "Flats, offices, single pieces of furniture. Evenings and weekends too."],
        ['Piano lessons for beginners — prepayment only', "Lessons at your home. Pay in advance by transfer to a card before the first lesson."],
    ],
    'reject_reason' => 'Ask for payment only when the buyer has seen the item. Please remove the request for prepayment and send the ad again.',
    'stop_words' => ['prepayment only', 'transfer to a card'],
];
