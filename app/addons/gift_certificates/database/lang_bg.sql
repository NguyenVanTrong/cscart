REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'В процес на обработка' as description,
    'е създаден.' as email_subj,
    '<p>Вашият талон за подарък е създаден успешно. Моля, изчакайте да бъде активиран.</p>' as email_header,
    'bg' as lang_code
FROM ?:statuses
WHERE status = 'P' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Активен' as description,
    'е активен.' as email_subj,
    '<p>Вашият талон за подарък е в статус активен и можете да го използвате.</p>' as email_header,
    'bg' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Анулиран' as description,
    'е анулиран.' as email_subj,
    '<p>Вашият талон за подарък е анулиран. За повече информация се свържете с администратор на сайта.</p>' as email_header,
    'bg' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Използван' as description,
    'и използван.' as email_subj,
    '<p>Вашият талон за подарък е използван. Благодарим Ви, че изпрахте нас!</p>' as email_header,
    'bg' as lang_code
FROM ?:statuses
WHERE status = 'U' AND type = 'G';