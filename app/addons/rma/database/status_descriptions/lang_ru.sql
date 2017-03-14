REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Рассматриваемый' as description,
    'был создан успешно.' as email_subj,
    'Ваш запрос на возврат был создан успешно.' as email_header,
    'ru' as lang_code
FROM ?:statuses
WHERE status = 'R' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Подтвежден' as description,
    'был подтвержден' as email_subj,
    'Ваш запрос на возврат был подтвержден' as email_header,
    'ru' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Отклонен' as description,
    'был отклонен' as email_subj,
    'Ваш запрос на возврат был отклонен. Пожалуйста, свяжитесь с администрацией магазина.' as email_header,
    'ru' as lang_code
FROM ?:statuses
WHERE status = 'D' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Выполнен' as description,
    'был выполнен' as email_subj,
    'Ваш запрос на возврат был выполнен. Спасибо, что выбрали нас.' as email_header,
    'ru' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'R';