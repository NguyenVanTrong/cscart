REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Zahteva' as description,
    'zahteva je bila uspešna' as email_subj,
    'Vaša vrnitev je bila zahtevana uspešno.' as email_header,
    'sl' as lang_code
FROM ?:statuses
WHERE status = 'R' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Odobreno' as description,
    'je bilo odobreno.' as email_subj,
    'Vaša vrnitev je bila odobrena.' as email_header,
    'sl' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Zavrnjeno' as description,
    'je bilo zavrnjeno' as email_subj,
    'Vaša vrnitev je bila zavrnjena. Prosimo, kontaktirajte administratorja.' as email_header,
    'sl' as lang_code
FROM ?:statuses
WHERE status = 'D' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Končano' as description,
    'je bilo končano.' as email_subj,
    'Vaše vračilo je bilo končano. Hvala, ker ste nas izbrali.' as email_header,
    'sl' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'R';