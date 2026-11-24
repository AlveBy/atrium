<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => '1C 連携',

    'settings' => [
        'description' => 'カタログは 1C:Enterprise から標準の CommerceML 連携で取り込まれます。1C のサイトとの連携設定に次のアドレスを指定してください：:url（サイトのみを尋ねる構成では、サイトのアドレスの後に :bitrix を付けます）。その下には、ここで設定したログインとパスワードを入力します。',
        'login' => 'ログイン',
        'login_hint' => '1C がサインインに使うものです。ログインとパスワードの両方を設定するまで、連携は閉じています。',
        'password' => 'パスワード',
        'password_hint' => '暗号化して保存され、再表示されません。変更するには新しいものを入力してください。空欄のままにすると現在のものを維持します。',
    ],

    'fields' => [
        'started_at' => '開始',
        'finished_at' => '終了',
        'file' => 'ファイル',
        'status_label' => '結果',
        'sections_created' => '新規セクション',
        'sections_updated' => '更新したセクション',
        'products_created' => '新規商品',
        'products_updated' => '更新した商品',
        'offers_updated' => '更新した価格と在庫',
        'skipped' => 'スキップしたオファー（該当する商品なし）',
        'errors' => 'エラー',
        'error' => '問題の内容',
    ],

    'status' => [
        'running' => '実行中',
        'done' => '完了',
        'failed' => '失敗',
    ],

    'reply' => [
        'wrong_login' => 'ログインまたはパスワードが違うか、連携が「モジュール → 1C」で設定されていません。',
        'denied' => 'サインインしていません。連携をもう一度開始してください。',
        'unknown_type' => '不明な連携タイプ：:type。',
        'unknown_mode' => '不明な連携ステップ：:mode。',
        'bad_name' => 'このファイル名は使用できません。',
        'no_file' => 'ファイル :file が届きませんでした。',
        'progress' => 'インポートした項目：:count。',
        'summary' => 'セクション：新規 :sections_created、更新 :sections_updated。商品：新規 :products_created、更新 :products_updated。価格と在庫：:offers。スキップ：:skipped。エラー：:errors。',
        'no_orders' => '注文は連携されません。ショップモジュールがインストールされていません。',
    ],
];
