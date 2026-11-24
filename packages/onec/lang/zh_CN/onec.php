<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => '1C 数据交换',

    'settings' => [
        'description' => '商品目录通过标准 CommerceML 交换从 1C:Enterprise 获取。请在 1C 中与网站的交换设置里填写此地址：:url（只询问网站的配置，在网站地址后加 :bitrix），下方填写您在此设置的登录名和密码。',
        'login' => '登录名',
        'login_hint' => '1C 用它登录。登录名和密码都设置好之前，交换保持关闭。',
        'password' => '密码',
        'password_hint' => '加密保存，不会再次显示：要更改请输入新密码；留空则保留当前密码。',
    ],

    'fields' => [
        'started_at' => '开始时间',
        'finished_at' => '完成时间',
        'file' => '文件',
        'status_label' => '结果',
        'sections_created' => '新增分类',
        'sections_updated' => '已更新分类',
        'products_created' => '新增商品',
        'products_updated' => '已更新商品',
        'offers_updated' => '已更新价格和库存',
        'skipped' => '跳过的报价（无此商品）',
        'errors' => '错误',
        'error' => '出了什么问题',
    ],

    'status' => [
        'running' => '进行中',
        'done' => '完成',
        'failed' => '失败',
    ],

    'reply' => [
        'wrong_login' => '登录名或密码错误，或未在"模块 → 1C"中设置交换。',
        'denied' => '未登录：请重新开始交换。',
        'unknown_type' => '未知的交换类型：:type。',
        'unknown_mode' => '未知的交换步骤：:mode。',
        'bad_name' => '不允许使用此文件名。',
        'no_file' => '文件 :file 未收到。',
        'progress' => '已导入项目：:count。',
        'summary' => '分类：新增 :sections_created，更新 :sections_updated。商品：新增 :products_created，更新 :products_updated。价格和库存：:offers。跳过：:skipped。错误：:errors。',
        'no_orders' => '不交换订单：未安装商店模块。',
    ],
];
