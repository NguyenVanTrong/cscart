{include file="common/letter_header.tpl"}

{__("hello")},<br /><br />

{if ($status_from == "A" && $status_to == "D") || ($status_from == "D" && $status_to == "A") || ($status_from == "P" && $status_to == "D") || ($status_from == "D" && $status_to == "P")}
    {__("text_company_status_changed", ["[company]" => $company_data.company_name, "[status]" => $status])}
{elseif $status_from == "A" && $status_to == "P"}
    {__("text_company_status_active_to_pending", ["[company]" => $company_data.company_name])}
{elseif $status_from == "N" && $status_to == "A"}
    {__("text_company_status_new_to_active", ["[company]" => $company_data.company_name])}
{elseif $status_from == "N" && $status_to == "D"}
    {__("text_company_status_new_to_disable", ["[company]" => $company_data.company_name])}
{elseif $status_from == "N" && $status_to == "P"}
    {__("text_company_status_new_to_pending", ["[company]" => $company_data.company_name])}
{elseif $status_from == "P" && $status_to == "A"}
    {__("text_company_status_pending_to_active", ["[company]" => $company_data.company_name])}
{/if}

<br /><br />

{if $reason}
{__("reason")}: {$reason}
<br /><br />
{/if}

{if $e_account == 'updated'}
    {__("text_company_status_new_to_active_administrator_updated", ["[link]" => $vendor_url, "[link_text]" => $vendor_url|puny_decode, "[login]" => $e_username])}
{elseif $e_account == 'new'}
    {__("text_company_status_new_to_active_administrator_created", ["[link]" => $vendor_url, "[link_text]" => $vendor_url|puny_decode, "[login]" => $e_username, "[password]" => $e_password])}
{/if}

{include file="common/letter_footer.tpl"}
