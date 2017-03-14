REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Pendiente' as description,
    'ha sido creado.' as email_subj,
    'Su certificado de regalo ha sido creado con éxito. Por favor espere hasta que sea activado.' as email_header,
    'es' as lang_code
FROM ?:statuses
WHERE status = 'P' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Activo' as description,
    'ha sido activado.' as email_subj,
    'Su certificado de regalo está activo. Usted puede usarlo ahora.' as email_header,
    'es' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Cancelado' as description,
    'ha sido cancelado.' as email_subj,
    'Su certificado de regalo ha sido cancelado. Por favor contacte con la administración de la tienda.' as email_header,
    'es' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Usado' as description,
    'ha sido usado.' as email_subj,
    'Su certificado de regalo ha sido utilizado. Gracias por elegirnos.' as email_header,
    'es' as lang_code
FROM ?:statuses
WHERE status = 'U' AND type = 'G';