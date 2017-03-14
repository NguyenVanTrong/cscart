REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'În așteptare' as description,
    'a fost creat.' as email_subj,
    'Certificatul dvs. de cadou a fost creat cu succes. Vă rugăm să așteptați până când va fi activat.' as email_header,
    'ro' as lang_code
FROM ?:statuses
WHERE status = 'P' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Activ' as description,
    'a fost activat.' as email_subj,
    'Certificatul dvs. de cadou are statutul Activ. Îl puteți utiliza acum.' as email_header,
    'ro' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Anulat' as description,
    'a fost anulat.' as email_subj,
    'Certificatul dvs. de cadou a fost anulat. Vă rugăm contactați administrația magazinului.' as email_header,
    'ro' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Utilizat' as description,
    'a fost utilizat.' as email_subj,
    'Certificatul dvs. de cadou este utilizat. Vă mulțumim că ne-ați ales.' as email_header,
    'ro' as lang_code
FROM ?:statuses
WHERE status = 'U' AND type = 'G';