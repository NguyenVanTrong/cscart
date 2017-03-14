REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Anmodning' as description,
    'Din anmoding er blevet godkendt' as email_subj,
    'Din anmodning er blevet godkendt' as email_header,
    'da' as lang_code
FROM ?:statuses
WHERE status = 'R' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Godkendt' as description,
    'Er blevet godkendt' as email_subj,
    'Din RMA er blevet godkendt' as email_header,
    'da' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Afvist' as description,
    'Er blevet afvist' as email_subj,
    'Din RMA er blevet afvist. Kontakt venligst butikkens administration' as email_header,
    'da' as lang_code
FROM ?:statuses
WHERE status = 'D' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Færdig behandlet' as description,
    'Er færdig behandlet' as email_subj,
    'Din RMA er afsluttet. Tak fordi du valgte os' as email_header,
    'da' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'R';