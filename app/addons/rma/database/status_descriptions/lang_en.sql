REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Requested' as description,
    'has been requested successfully.' as email_subj,
    'Your return has been requested successfully.' as email_header,
    'en' as lang_code
FROM ?:statuses
WHERE status = 'R' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Approved' as description,
    'has been approved.' as email_subj,
    'Your return has been approved.' as email_header,
    'en' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Declined' as description,
    'has been declined.' as email_subj,
    'Your return has been declined. Please contact shop administration.' as email_header,
    'en' as lang_code
FROM ?:statuses
WHERE status = 'D' AND type = 'R';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Completed' as description,
    'has been completed.' as email_subj,
    'Your return has been completed. Thank you for choosing us.' as email_header,
    'en' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'R';