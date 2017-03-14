REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Solicitada' as description,
    'ha sido solicitada con éxito.' as email_subj,
    'Su petición de devolución ha sido solicitada con éxito.' as email_header,
    'es' as lang_code
FROM ?:statuses
WHERE status = 'R' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Aceptada' as description,
    'ha sido aceptada.' as email_subj,
    'Su petición de devolución ha sido aprobada.' as email_header,
    'es' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Rechazada' as description,
    'ha sido rechazada.' as email_subj,
    'Su petición de devolución ha sido rechazada. Por favor contacta con la administración de la tienda.' as email_header,
    'es' as lang_code
FROM ?:statuses
WHERE status = 'D' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Completada' as description,
    'ha sido completada.' as email_subj,
    'Su petición de devolución ha sido completada. Gracias por elegirnos.' as email_header,
    'es' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'R';