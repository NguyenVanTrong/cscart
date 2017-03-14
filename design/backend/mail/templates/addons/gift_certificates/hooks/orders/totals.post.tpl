{if $order_info.use_gift_certificates}
{foreach from=$order_info.use_gift_certificates item="certificate" key="code"}
<tr>
    <td colspan="2" style="text-align: right; unicode-bidi: bidi-override; font-size: 12px; font-family: Arial;">
        <b style="unicode-bidi: embed;">{__("gift_certificate")}</b> <span style="unicode-bidi: embed;">{$code}</span> ({include file="common/price.tpl" value=$certificate.cost})</td>
</tr>
{/foreach}
{/if}