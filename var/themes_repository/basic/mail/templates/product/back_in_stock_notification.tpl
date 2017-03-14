{include file="common/letter_header.tpl"}

{__("dear")} {__("customer")},<br /><br />

{__("back_in_stock_notification_header")}<br /><br />

<b><a href="{$url}">{$product.name nofilter}</a></b><br /><br />

{__("back_in_stock_notification_footer")}<br />

{include file="common/letter_footer.tpl"}
