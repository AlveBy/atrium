<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'トラッカー',
    'my_tasks' => '自分のタスク',
    'hours_short' => '時間',
    'thread_empty' => 'まだ何もありません。',

    'projects' => [
        'title' => 'プロジェクト',
        'add' => 'プロジェクトを追加',
        'fields' => [
            'name' => '名前',
            'key' => 'キー',
            'key_hint' => '文字で始まる2〜10文字の大文字のラテン文字または数字。タスクは ATR-1、ATR-2 … のように番号が付きます',
            'description' => '説明',
            'lead' => 'リーダー',
            'members' => 'メンバー',
            'members_hint' => 'プロジェクトで働く管理者。タスクを割り当てられるのは彼らとリーダーだけです。全員に許可するには空のままにします。',
            'archived' => 'アーカイブ済み',
            'archived_hint' => 'アーカイブしたプロジェクトには新しいタスクを入れられません。既存のタスクは残ります。',
        ],
    ],

    'tasks' => [
        'title' => 'タスク',
        'add' => 'タスクを追加',
        'tabs' => [
            'params' => 'タスク',
            'files' => 'ファイル',
            'activity' => 'コメントと履歴',
        ],
        'fields' => [
            'code' => 'タスク',
            'project' => 'プロジェクト',
            'type' => '種類',
            'title' => 'タイトル',
            'description' => '説明',
            'status' => 'ステータス',
            'priority' => '優先度',
            'resolution' => '解決状況',
            'assignee' => '担当者',
            'parent' => '親タスク',
            'parent_hint' => '同じプロジェクトのタスクのサブタスク、またはエピックのタスク。',
            'labels' => 'ラベル',
            'add_label' => 'ラベルを追加',
            'due_date' => '期限',
            'estimate' => '見積もり',
            'attachments' => '添付ファイル',
            'new_comment' => '新しいコメント',
            'new_comment_hint' => '保存するとタスクに追加されます。',
            'thread' => 'コメントと履歴',
        ],
        'filters' => [
            'label' => 'ラベル',
            'overdue' => '期限切れ',
            'mine' => '自分のタスク',
        ],
    ],

    'comments' => [
        'title' => 'タスクのコメント',
        'fields' => [
            'body' => 'コメント',
            'task' => 'タスク',
            'author' => '作成者',
            'created' => '投稿日時',
        ],
    ],

    'terms' => [
        'types' => 'タスクの種類',
        'priorities' => '優先度',
        'statuses' => 'ステータス',
        'resolutions' => '解決状況',
        'fields' => [
            'code' => 'コード',
            'code_hint' => '小文字、数字、_。タスクはこのコードを保持するため、後から変更できません。',
            'name' => '名前',
            'name_hint' => '空の場合: 組み込みの名前が、各管理者の言語で表示されます。',
            'sort' => '並び順',
            'default' => '既定',
            'default_hint' => '新しいタスクにはこれが設定されます。印がなければ最初のもの。',
            'category' => 'カテゴリ',
            'symbol' => '記号',
            'symbol_hint' => '名前の前に表示され、優先度が色だけで区別されないようにします。',
            'top_level' => '親なし',
            'top_level_hint' => 'この種類のタスクは、エピックのように最上位に置かれます。',
            'needs_parent' => '親が必要',
            'needs_parent_hint' => 'この種類のタスクは、サブタスクのように常に別のタスクに属します。',
        ],
        'categories' => [
            'todo' => '未着手',
            'active' => '進行中',
            'done' => '完了',
        ],
    ],

    'types' => [
        'task' => 'タスク',
        'bug' => 'バグ',
        'story' => 'ストーリー',
        'epic' => 'エピック',
        'subtask' => 'サブタスク',
    ],

    'resolutions' => [
        'done' => '完了',
        'wont_do' => '対応しない',
        'duplicate' => '重複',
        'cannot_reproduce' => '再現不可',
    ],

    'priorities' => [
        'lowest' => '最低',
        'low' => '低',
        'medium' => '中',
        'high' => '高',
        'highest' => '最高',
    ],

    'statuses' => [
        'todo' => '未着手',
        'in_progress' => '進行中',
        'review' => 'レビュー中',
        'done' => '完了',
    ],

    'kinds' => [
        'comment' => 'コメント',
        'created' => '作成',
        'status' => 'ステータス',
        'priority' => '優先度',
        'assignee' => '担当者',
    ],

    'events' => [
        'created' => 'タスクが作成されました。',
        'status' => 'ステータス: :old → :new',
        'priority' => '優先度: :old → :new',
        'assignee' => '担当者: :old → :new',
    ],

    'errors' => [
        'archived' => 'プロジェクトはアーカイブされています。タスクを移動できません。',
        'not_in_project' => 'この管理者はプロジェクトで働いていません。',
        'type_has_no_parent' => 'この種類のタスクには親タスクがありません。',
        'needs_parent' => 'この種類のタスクには親タスクが必要です。',
        'in_use' => 'これを使っているタスクがあります。先にタスクを変更してください。',
        'parent_other_project' => '親タスクは同じプロジェクトのものにしてください。',
        'parent_loop' => 'タスクは自分自身や自分のサブタスクの子にはできません。',
    ],

    'settings' => [
        'description' => 'タスクに関するメール。',
        'notify_assigned' => '担当者にメールする',
        'notify_assigned_hint' => 'タスクが管理者に割り当てられたときのメール。',
        'notify_status' => '新しいステータスをメールする',
        'notify_status_hint' => '担当者のタスクのステータスが変わったときのメール。',
        'disabled_plugins' => 'オフにしたプラグイン',
        'disabled_plugins_hint' => 'チェックしたプラグインは無視されます。新しいプラグインはチェックするまでオンです。',
    ],

    'mail' => [
        'assigned_subject' => ':code があなたに割り当てられました: :title',
        'assigned_line' => 'タスク :code「:title」があなたに割り当てられました。',
        'status_subject' => ':code は「:status」になりました',
        'status_line' => 'タスク :code「:title」のステータスが変わりました: :old → :new。',
    ],
];
