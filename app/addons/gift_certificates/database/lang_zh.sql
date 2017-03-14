REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '待定的' as description,
    '已创建.' as email_subj,
    '<p>您的礼卷已成功创建.请等待它被启用.</p>' as email_header,
    'zh' as lang_code
FROM ?:statuses
WHERE status = 'P' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '已启用' as description,
    '已启用.' as email_subj,
    '<p>您的礼卷状态已启用.您现在可以使用.</p>' as email_header,
    'zh' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '已取消' as description,
    '已取消.' as email_subj,
    '<p>您的礼卷已取消.请联系商店管理员.</p>' as email_header,
    'zh' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    '已使用' as description,
    '已使用.' as email_subj,
    '<p>您的礼卷已失效. 感谢您选择我们.</p>' as email_header,
    'zh' as lang_code
FROM ?:statuses
WHERE status = 'U' AND type = 'G';