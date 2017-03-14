REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Рассматриваемый' as description,
    'был создан.' as email_subj,
    'Ваш подарочный сертификат был создан успешно. Пожалуйста, подождите пока он не будет активирован.' as email_header,
    'ru' as lang_code
FROM ?:statuses
WHERE status = 'P' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Активен' as description,
    'был активирован.' as email_subj,
    '<p>Ваш подарочный сертификат был активирован. Вы можете использовать его.</p>' as email_header,
    'ru' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Неактивен' as description,
    'неактивен.' as email_subj,
    'Ваш подарочный сертификат неактивен. Пожалуйста, свяжитесь с администрацией магазина.' as email_header,
    'ru' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Использован' as description,
    'был использован.' as email_subj,
    'Ваш подарочный сертификат был использован. Спасибо, что выбрали нас.' as email_header,
    'ru' as lang_code
FROM ?:statuses
WHERE status = 'U' AND type = 'G';