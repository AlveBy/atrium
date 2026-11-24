<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '連携',
    'title_log' => '交換ログ',

    'status' => [
        'ok' => '完了',
        'retry' => '失敗(再試行します)',
        'failed' => '失敗',
    ],
    'direction' => [
        'out' => 'サイトから',
        'in' => 'サイトへ',
    ],
    'fields' => [
        'when' => '日時',
        'integration' => '連携',
        'direction' => '方向',
        'action' => '操作',
        'subject' => '対象',
        'status' => '結果',
        'message' => '実行内容',
        'error' => 'エラー',
        'attempt' => '試行回数',
        'duration' => '所要時間(ms)',
    ],

    'settings' => [
        'description' => 'コネクタ(1C、Bitrix24)が最後の試行後に処理を断念したときの通知先と、交換ログの保存期間。',
        'alert_emails' => 'アラート用メールアドレス',
        'alert_emails_hint' => '1件以上のアドレスをカンマまたは改行で区切って入力します。空欄の場合は有効なすべてのスーパー管理者に送信します。連携ごとに1時間に最大1通です。',
        'retention_days' => 'ログの保存期間(日)',
        'retention_days_hint' => '古い記録は毎晩削除されます。',
    ],

    'alert' => [
        'subject' => ':site:連携 :integration が失敗しました',
        'what' => '連携 :integration は :action を実行できず、再試行を終了しました。',
        'subject_line' => '対象: :subject',
        'error' => 'エラー: :error',
        'attempts' => '試行回数: :count',
    ],
];
