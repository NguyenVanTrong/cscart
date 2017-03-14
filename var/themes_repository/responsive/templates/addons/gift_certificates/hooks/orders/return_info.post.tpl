{if $return_info.extra.gift_certificates}
<div class="ty-gift-certificate-order">
    <td class="ty-gift-certificate-order__group-label"><strong>{__("gift_certificates")}</strong>:&nbsp;</td>
    <td>
        {foreach from=$return_info.extra.gift_certificates item="gift_cert" key="gift_cert_key"}
            <div>
                <a class="ty-btn ty-btn__text" href="{"gift_certificates.verify?verify_code=`$gift_cert.code`"|fn_url}">{$gift_cert.code}</a>&nbsp;({include file="common/price.tpl" value=$gift_cert.amount})
            </div>
        {/foreach}
    </td>
</div>
{/if}
