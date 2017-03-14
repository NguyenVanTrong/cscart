UPDATE ?:pages SET company_id = 1 WHERE page_id IN (7,8,9,10);
INSERT INTO ?:ult_objects_sharing (`share_company_id`, `share_object_id`, `share_object_type`) SELECT 1 as share_company_id, page_id as share_object_id, 'pages' as share_object_type FROM ?:pages WHERE page_id IN (7,8,9,10);
