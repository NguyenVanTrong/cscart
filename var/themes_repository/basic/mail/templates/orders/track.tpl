{include file="common/letter_header.tpl"}

{__("hello")},<br /><br />

{__("text_track_request")}<br /><br />

{if $o_id}
{__("text_track_view_order", ["[order]" => $o_id])}<br />
<a href="{$url}">{$url}</a><br />
<br />
{/if}

{__("text_track_view_all_orders")}<br />
<a href="{$track_all_url}">{$track_all_url}</a><br />

{include file="common/letter_footer.tpl"}