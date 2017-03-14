{include file="common/letter_header.tpl"}

{__("dear")} {if $user_data.firstname}{$user_data.firstname}{else}{$user_data.user_type|fn_get_user_type_description|lower}{/if},<br><br>

{__("change_password_notification_body", ["[days]" => $days, "[store]" => $store_url|puny_decode])}<br><br>

<a href="{$url}">{$url|puny_decode}</a><br><br>

{include file="common/letter_footer.tpl"}