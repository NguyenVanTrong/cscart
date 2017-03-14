REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Na čakanju' as description,
    'je na čakanju' as email_subj,
    'Vaš darilni bon je bil uspešno ustvarjen. Prosimo, počakajte, da se aktivira.' as email_header,
    'sl' as lang_code
FROM ?:statuses
WHERE status = 'P' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Aktivno' as description,
    'je bilo aktivirano.' as email_subj,
    'Vaš darilni bon ima status Aktiven. Sedaj ga lahko uporabite.' as email_header,
    'sl' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Preklicano' as description,
    'je bilo preklicano.' as email_subj,
    'Vaš darilni bon je bil preklican. Prosimo, kontaktirajte administratorja.' as email_header,
    'sl' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Uporabljen' as description,
    'je bil uporabljen' as email_subj,
    'Vaš darilni bon je porabljen. Hvala, ker ste nas izbrali.' as email_header,
    'sl' as lang_code
FROM ?:statuses
WHERE status = 'U' AND type = 'G';