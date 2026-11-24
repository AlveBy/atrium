<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => 'Bitrix24: フォーム',

    'settings' => [
        'description' => 'フォームの送信内容は、リードとして Bitrix24 に届き、Bitrix24 のチャットウィジェットがサイトに表示されます。Bitrix24 で CRM の権限を持つ着信 Webhook(開発者向けリソース → その他 → 着信 Webhook)を作成し、そのアドレスをここに貼り付けてください。',
        'webhook' => '着信 Webhook',
        'webhook_hint' => 'Bitrix24 に表示されるアドレスです。例: https://portal.bitrix24.com/rest/1/code/。暗号化して保存され、二度と表示されません。変更するには新しいものを貼り付けてください。現在のものを残すには、欄を空のままにします。',
        'send_forms' => 'フォームの送信内容を Bitrix24 に送る',
        'send_forms_hint' => '下で関連付けていないフォームは、リードとして送られ、各項目は種類に応じて振り分けられます。',
        'widget_code' => 'チャットウィジェットのコード',
        'widget_code_hint' => 'Bitrix24 のオープンチャンネルまたはチャットウィジェットのコードです(CRM → コンタクトセンター → オンラインチャット → ウィジェット)。サイトの全ページの末尾に挿入されます。空の場合はウィジェットは表示されません。',
        'check' => '接続を確認',
        'connected' => '接続しました: :portal、ユーザー :user。',
    ],

    'fields' => [
        'form' => 'フォーム',
        'mode' => '送信形式',
        'active' => 'このフォームを Bitrix24 に送る',
        'mapping' => '項目',
        'mapping_hint' => '1項目につき1行で、「フォームの項目 = Bitrix24 の項目」の形式で書きます。例: "phone = PHONE"、"message = COMMENTS"。行がない項目は種類に応じて振り分けられます(メールは EMAIL、電話は PHONE)。振り分け先のないものはコメントに入ります。# で始まる行は無視されます。',
    ],

    'modes' => [
        'lead' => 'リード',
        'deal' => '取引(コンタクト付き)',
    ],

    'errors' => [
        'no_webhook' => 'Bitrix24 の Webhook が設定されていません。「モジュール → Bitrix24」で貼り付けてください。',
        'unreachable' => 'Bitrix24 から応答がありません。Webhook に含まれるポータルのアドレスを確認してください。',
        'portal' => 'Bitrix24 に拒否されました: :error',
        'mapping_line' => ':line 行目が「フォームの項目 = Bitrix24 の項目」の形式になっていません。',
    ],

    'payload' => [
        'subject' => 'フォーム送信 #:id',
        'title' => 'ウェブサイト: :form',
        'visitor' => 'サイトの訪問者',
        'yes' => 'はい',
        'lead' => 'リード #:id を作成しました。',
        'deal' => '取引 #:deal を作成しました。コンタクト #:contact。',
        'gone' => '送信前にこの送信内容が削除されました。',
    ],
];
