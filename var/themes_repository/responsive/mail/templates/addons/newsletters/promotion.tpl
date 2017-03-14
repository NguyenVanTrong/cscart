{include file="common/letter_header.tpl"}

<h1>{$promotion.name}</h1>

{__("discount_coupon_code")}: <b>{$coupon}</b>

{$promotion.short_description nofilter}<br/>

{__("more_info")}: <a href="{$url}">{$url|puny_decode}</a>

{include file="common/letter_footer.tpl"}