{include file="common/letter_header.tpl"}

{__("dear")} {$order_info.firstname},<br /><br />

{__("edp_access_granted")}<br /><br />

{foreach from=$edp_data item="product"}
<a href="{$product.url}"><b>{$product.product}</b></a><br />
<p></p>
{foreach from=$product.files item="file"}
<a href="{$file.url}">{$file.file_name} ({$file.file_size|number_format:0:'':' '}&nbsp;{__("bytes")})</a><br /><br />
{/foreach}
{/foreach}

{include file="common/letter_footer.tpl"}