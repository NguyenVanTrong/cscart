REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Pending' as description,
    'has been created.' as email_subj,
    'Your gift certificate has been created successfully. Please wait until it is activated.' as email_header,
    'en' as lang_code
FROM ?:statuses
WHERE status = 'P' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Active' as description,
    'has been activated.' as email_subj,
    'Your gift certificate has status Active. You can use it now.' as email_header,
    'en' as lang_code
FROM ?:statuses
WHERE status = 'A' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Disabled' as description,
    'has been disabled.' as email_subj,
    'Your gift certificate has been disabled. Please contact shop administration.' as email_header,
    'en' as lang_code
FROM ?:statuses
WHERE status = 'C' AND type = 'G';

REPLACE INTO ?:status_descriptions (`status_id`, `description`, `email_subj`, `email_header`, `lang_code`)
SELECT
    status_id,
    'Used' as description,
    'has been used.' as email_subj,
    'Your gift certificate is spent. Thank you for choosing us.' as email_header,
    'en' as lang_code
FROM ?:statuses
WHERE status = 'U' AND type = 'G';