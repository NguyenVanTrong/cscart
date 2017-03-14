REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Solicitat' as description,
    'a fost solicitat cu success.' as email_subj,
    'Cererea dvs. de returnare a fost depusă cu succes.' as email_header,
    'ro' as lang_code
FROM ?:statuses
WHERE status = 'R' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Aprobat' as description,
    'a fost aprobat.' as email_subj,
    'Solicitarea dvs. de returnare a fost aprobată.' as email_header,
    'ro' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Respins' as description,
    'a fost respins.' as email_subj,
    'Solicitarea dvs. de returnare a fost respinsă. Vă rugăm contactați administrația magazinului.' as email_header,
    'ro' as lang_code
FROM ?:statuses
WHERE status = 'D' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Completă' as description,
    'a fost finalizat.' as email_subj,
    'Solicitarea dvs. de returnare a fost finalizată. Vă mulțumim că ne-ați ales.' as email_header,
    'ro' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'R';