<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Simplified Chinese: a made-up company in China.
 *
 * `country` is used when Simplified Chinese is the main language of the
 * install: the offices, the people, the currency and the usual way to
 * state a salary there. `texts` are the vacancies in Simplified Chinese,
 * used whatever the main country is, so they name no city, currency or
 * law. Everything is fictional; e-mail addresses are the part before the
 * @, the domain is `domain`.
 */
return [
    'country' => [
        'code' => 'CN',
        'currency' => 'CNY',
        'domain' => 'example.cn',
        'offices' => [
            ['name' => '上海', 'street' => '浦东新区示例路1200号3楼', 'postal_code' => '200120', 'region' => null],
            ['name' => '深圳', 'street' => '南山区样板大道400号', 'postal_code' => '518000', 'region' => '广东省'],
        ],
        'contact' => ['name' => '王静', 'email' => 'hr', 'phone' => '+86 21 0000 0142'],
        // Gross. A month is how salaries are stated in China; interns get a
        // monthly allowance (daily pay is also common, the demo keeps month).
        'salaries' => [
            'backend' => ['from' => 18000, 'to' => 28000, 'period' => 'month'],
            'designer' => ['from' => 15000, 'to' => 22000, 'period' => 'month'],
            'account' => null,
            'intern' => ['from' => 4000, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => '李明', 'email' => 'li.ming'],
            ['name' => '张婷', 'email' => 'zhang.ting'],
            ['name' => '陈雪', 'email' => 'chen.xue'],
            ['name' => '刘洋', 'email' => 'liu.yang'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => '研发',
            'design' => '设计',
            'sales' => '销售',
            'support' => '客户支持',
        ],
        'vacancies' => [
            'backend' => [
                'name' => '后端开发工程师（PHP/Laravel）',
                'code' => 'backend-engineer',
                'preview_text' => '加入一支每周发版的小团队，打造支撑我们产品的后端服务。',
                'description' => '<p>研发团队共有八名开发者。你将端到端负责功能开发，从数据库一直到 API。</p>',
                'duties' => '<ul><li>设计并开发 REST API</li><li>参与同事的代码评审</li><li>确保测试始终全部通过</li></ul>',
                'requirements' => '<ul><li>3 年以上 PHP 开发经验</li><li>熟悉 Laravel、SQL、Git</li><li>良好的书面表达能力</li></ul>',
                'conditions' => '<ul><li>每周两天到岗办公</li><li>补充商业医疗保险</li><li>每年提供学习培训预算</li></ul>',
            ],
            'designer' => [
                'name' => '产品设计师',
                'code' => 'product-designer',
                'preview_text' => '从第一张草图到正式上线，由你来塑造用户使用我们应用的方式。',
                'description' => '<p>设计团队共两人，你将与产品经理和开发工程师紧密合作。</p>',
                'duties' => '<ul><li>开展用户访谈并测试原型</li><li>维护设计系统，保持规范统一</li></ul>',
                'requirements' => '<ul><li>作品集中有已上线的产品</li><li>熟练使用 Figma</li></ul>',
                'conditions' => '<ul><li>可在全国任意地点远程办公</li><li>笔记本电脑和显示器任你挑选</li></ul>',
            ],
            'account' => [
                'name' => '客户经理',
                'code' => 'account-manager',
                'preview_text' => '服务我们的企业客户，帮助他们更充分地发挥产品价值。',
                'description' => '<p>你将负责约三十家客户，背后有整个团队的支持。</p>',
                'duties' => '<ul><li>协助新客户完成上手与部署</li><li>每季度至少与每位客户见面一次</li></ul>',
                'requirements' => '<ul><li>一年以上销售或客户成功相关经验</li><li>文字表达清晰、亲切</li></ul>',
                'conditions' => '<ul><li>续约奖金</li><li>办公环境安静，公共交通便利</li></ul>',
            ],
            'intern' => [
                'name' => '客户支持实习生',
                'code' => 'support-intern',
                'preview_text' => '为期三个月，每周二十小时，有导师带教：进入 IT 行业的好起点。',
                'description' => '<p>你将通过邮件和在线聊天解答客户问题，并深入了解产品的内部运作。</p>',
                'duties' => '<ul><li>通过邮件和在线聊天回复客户</li><li>整理常见问题</li></ul>',
                'requirements' => '<ul><li>欢迎在校学生</li><li>有耐心，乐于助人</li></ul>',
                'conditions' => '<ul><li>时间灵活，不影响学业</li><li>表现优秀者有机会转正</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => '面试邀请',
                'subject' => '关于您应聘“:vacancy”职位',
                'body' => ":name，您好！\n\n感谢您应聘“:vacancy”职位。我们希望与您见面详谈，请回复下周您方便的两到三个时间段。\n\n顺祝商祺\n:company",
            ],
            'rejection' => [
                'name' => '婉拒通知',
                'subject' => '关于您应聘“:vacancy”职位',
                'body' => ":name，您好！\n\n感谢您对“:vacancy”职位的关注。经过慎重考虑，我们这次决定选择其他候选人。我们会保留您的简历，如有合适的职位空缺，将与您联系。\n\n顺祝商祺\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            '您好！我有五年 Laravel 开发经验，非常希望加入贵团队。',
            '我从事 PHP 和 PostgreSQL 开发六年，主要做 API。',
            '下午好！作品集链接在简历里。',
            '目前没有合适的职位，但我希望能以 QA 工程师的身份加入贵公司。',
        ],
    ],
];
