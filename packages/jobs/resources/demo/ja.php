<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Japanese: a made-up company in Japan.
 *
 * `country` is used when Japanese is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in Japanese, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'JP',
        'currency' => 'JPY',
        'domain' => 'example.co.jp',
        'offices' => [
            ['name' => '東京', 'street' => '港区見本町1-2-3 サンプルビル3階', 'postal_code' => '105-0000', 'region' => '東京都'],
            ['name' => '大阪', 'street' => '北区例示町4-5-6', 'postal_code' => '530-0000', 'region' => '大阪府'],
        ],
        'contact' => ['name' => '佐藤 美咲', 'email' => 'saiyo', 'phone' => '+81 3-0000-0142'],
        // Gross. A year is how salaries are stated in Japan (nenshu); interns
        // are paid by the hour.
        'salaries' => [
            'backend' => ['from' => 5500000, 'to' => 7500000, 'period' => 'year'],
            'designer' => ['from' => 5000000, 'to' => 6500000, 'period' => 'year'],
            'account' => null,
            'intern' => ['from' => 1500, 'period' => 'hour'],
        ],
        'applicants' => [
            ['name' => '鈴木 健太', 'email' => 'kenta.suzuki'],
            ['name' => '高橋 結衣', 'email' => 'yui.takahashi'],
            ['name' => '伊藤 さくら', 'email' => 'sakura.ito'],
            ['name' => '渡辺 翔', 'email' => 'sho.watanabe'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => '開発',
            'design' => 'デザイン',
            'sales' => '営業',
            'support' => 'カスタマーサポート',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'バックエンドエンジニア（PHP/Laravel）',
                'code' => 'backend-engineer',
                'preview_text' => '毎週リリースする少人数のチームで、プロダクトを支えるサービスを開発しませんか。',
                'description' => '<p>開発チームは8名です。データベースからAPIまで、機能開発を一貫して担当していただきます。</p>',
                'duties' => '<ul><li>REST APIの設計・開発</li><li>チームメンバーのコードレビュー</li><li>テストが常に通る状態の維持</li></ul>',
                'requirements' => '<ul><li>PHPでの開発経験3年以上</li><li>Laravel、SQL、Git</li><li>わかりやすい文章を書ける方</li></ul>',
                'conditions' => '<ul><li>週2日出社</li><li>医療保険の補助</li><li>年間の学習支援予算</li></ul>',
            ],
            'designer' => [
                'name' => 'プロダクトデザイナー',
                'code' => 'product-designer',
                'preview_text' => '最初のスケッチからリリースまで、お客様がアプリをどう使うかを形づくる仕事です。',
                'description' => '<p>デザイナーは2名体制で、プロダクトマネージャーやエンジニアと密に連携して進めます。</p>',
                'duties' => '<ul><li>ユーザーインタビューとプロトタイプの検証</li><li>デザインシステムの整備</li></ul>',
                'requirements' => '<ul><li>リリース済みプロダクトを含むポートフォリオ</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>国内どこからでもリモート勤務可</li><li>お好きなノートPCとモニターを支給</li></ul>',
            ],
            'account' => [
                'name' => 'アカウントマネージャー',
                'code' => 'account-manager',
                'preview_text' => '法人のお客様に寄り添い、プロダクトをより活用していただけるよう支援する仕事です。',
                'description' => '<p>担当はおよそ30社。困ったときはチーム全体でサポートします。</p>',
                'duties' => '<ul><li>新規のお客様の導入支援</li><li>四半期に1回以上、すべてのお客様と面談</li></ul>',
                'requirements' => '<ul><li>営業またはカスタマーサクセスの経験1年以上</li><li>明快で親しみやすい文章力</li></ul>',
                'conditions' => '<ul><li>契約更新に応じたインセンティブ</li><li>駅近の静かなオフィス</li></ul>',
            ],
            'intern' => [
                'name' => 'カスタマーサポート（インターン）',
                'code' => 'support-intern',
                'preview_text' => '期間は3か月、週20時間、メンター付き。IT業界でのキャリアの第一歩に。',
                'description' => '<p>メールとチャットでお客様からのご質問に回答しながら、プロダクトの仕組みを内側から学んでいただきます。</p>',
                'duties' => '<ul><li>メール・チャットでのお客様対応</li><li>よくある質問のとりまとめ</li></ul>',
                'requirements' => '<ul><li>学生歓迎</li><li>根気強く、人の役に立ちたい方</li></ul>',
                'conditions' => '<ul><li>学業に合わせてシフト調整可</li><li>優秀な方には正社員登用の機会あり</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => '面接のご案内',
                'subject' => '「:vacancy」へのご応募について',
                'body' => ":name 様\n\nこのたびは「:vacancy」にご応募いただき、誠にありがとうございます。ぜひ一度お会いしたく、来週でご都合のよい日時を2〜3つお知らせいただけますでしょうか。\n\nよろしくお願いいたします。\n:company",
            ],
            'rejection' => [
                'name' => '不採用のご連絡',
                'subject' => '「:vacancy」へのご応募について',
                'body' => ":name 様\n\nこのたびは「:vacancy」にご関心をお寄せいただき、誠にありがとうございました。慎重に検討いたしました結果、誠に残念ながら、今回はご期待に沿えない結果となりました。履歴書は保管させていただき、条件に合うポジションが生まれた際には改めてご連絡いたします。\n\n今後のご活躍をお祈り申し上げます。\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'はじめまして。Laravelでの開発経験が5年あり、ぜひチームに加わりたいと考えています。',
            'PHPとPostgreSQLを使った開発に6年携わり、主にAPIを担当してきました。',
            'こんにちは。ポートフォリオのリンクは履歴書に記載しています。',
            '現在合う求人はありませんが、QAエンジニアとして御社で働きたいと考えています。',
        ],
    ],
];
