REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Εκκρεμής' as description,
    'δημιουργήθηκε.' as email_subj,
    'Η δωροεπιταγή σας δημιουργήθηκε με επιτυχία. Παρακαλούμε περιμένετε μέχρι να ενεργοποιηθεί.' as email_header,
    'el' as lang_code
FROM ?:statuses
WHERE status = 'P' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Ενεργή' as description,
    'ενεργοποιήθηκε.' as email_subj,
    'Η δωροεπιταγή σας είναι ενεργή. Μπορείτε να την χρησιμοποιήσετε τώρα.' as email_header,
    'el' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Ακύρωση' as description,
    'Ακυρώθηκε.' as email_subj,
    'Η δωροεπιταγή σας ακυρώθηκε. Παρακαλούμε επικοινωνήστε με τον διαχειριστή του καταστήματος.' as email_header,
    'el' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Χρήση' as description,
    'χρησιμοποιήθηκε' as email_subj,
    'Η δωροεπιταγή σας χρησιμοποιήθηκε. Ευχαριστούμε που μας επιλέξατε.' as email_header,
    'el' as lang_code
FROM ?:statuses
WHERE status = 'U' AND type = 'G';