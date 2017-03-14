{include file="common/letter_header.tpl"}

{__("hello")},<br /><br />

{__("text_new_user_activation", ["[user_login]" => $user_data.email, "[url]" => $url, "[url_text]" => $url|puny_decode])}

{include file="common/letter_footer.tpl" user_type='A'}