REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Ζήτηση' as description,
    'ζητήθηκε επιτυχώς.' as email_subj,
    'Η αλλαγή σας ζητήθηκε επιτυχώς.' as email_header,
    'el' as lang_code
FROM ?:statuses
WHERE status = 'R' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Εγκριση' as description,
    'εγκρίθηκε.' as email_subj,
    'Η αλλαγή σας εγκρίθηκε.' as email_header,
    'el' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Απόρριψη' as description,
    'απορρίφτηκε.' as email_subj,
    'Η αλλαγή σας απορίφθηκε. Παρακαλούμε επικοινωνήστε με τον διαχειριστή του καταστήματος..' as email_header,
    'el' as lang_code
FROM ?:statuses
WHERE status = 'D' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Ολοκλήρωση' as description,
    'ολοκληρώθηκε.' as email_subj,
    'Η αλλαγή σας ολοκληρώθηκε. Ευχαριστούμε που μας επιλέξατε.' as email_header,
    'el' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'R';