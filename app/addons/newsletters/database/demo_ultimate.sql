REPLACE INTO ?:ult_objects_sharing (`share_company_id`, `share_object_id`, `share_object_type`)
    SELECT cc.company_id, m.list_id, 'mailing_lists' FROM ?:mailing_lists m INNER JOIN ?:companies cc;
