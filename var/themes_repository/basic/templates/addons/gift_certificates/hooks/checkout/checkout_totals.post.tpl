{if $cart.use_gift_certificates}
{foreach from=$cart.use_gift_certificates item="ugc" key="ugc_key"}
    <li class="group-title">
        <span class="checkout-item-title">{__("gift_certificate")}</span>
    </li>
    <li>
    <span class="checkout-item-title"><a href="{"gift_certificates.verify?verify_code=`$ugc_key`"|fn_url}">{$ugc_key}</a>
        {include file="addons/gift_certificates/views/gift_certificates/components/delete_button.tpl" code=$ugc_key r_url=$config.current_url|escape:url}
    </span>
    <span class="checkout-item-value discount-price">-{include file="common/price.tpl" value=$ugc.cost}</span>
    </li>
{/foreach}
{/if}
