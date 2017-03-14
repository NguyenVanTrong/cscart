REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Afventende' as description,
    'er blevet oprettet.' as email_subj,
    'Dit gavekort er blevet oprettet. Vent venligst, indtil det er aktiveret.' as email_header,
    'da' as lang_code
FROM ?:statuses
WHERE status = 'P' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Aktiv' as description,
    'Er blevet aktiveret' as email_subj,
    'Gavekortet har status som Aktiv. Du kan bruge det nu.' as email_header,
    'da' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Annulleret' as description,
    'Er blevet annulleret' as email_subj,
    'Dit gavekort er blevet annulleret. Kontakt venligst butikkens administration.' as email_header,
    'da' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Brugt' as description,
    'Er blevet brugt' as email_subj,
    'Gavekortet er brugt. Tak fordi du valgte os.' as email_header,
    'da' as lang_code
FROM ?:statuses
WHERE status = 'U' AND type = 'G';