{hook name="order_management:shipping_method"}
<div class="control-group">
    <div class="control-label">
        <h4 class="subheader">{__("shipping_method")}</h4>
    </div>
</div>
    {if $product_groups}
        {foreach from=$product_groups key=group_key item=group}
            <div class="control-group">
            <label class="control-label"> {$group.name|default:__("none")}</label>
            {if $group.shippings && !$group.shipping_no_required}
                <div class="controls">
                    <select name="shipping_ids[{$group_key}]" class="cm-submit cm-ajax cm-skip-validation" data-ca-dispatch="dispatch[order_management.update_shipping]">
                    {foreach from=$group.shippings item=shipping}
                        <option value="{$shipping.shipping_id}" {if $cart.chosen_shipping.$group_key == $shipping.shipping_id}selected="selected"{/if}>{$shipping.shipping} ({$shipping.delivery_time}) - {include file="common/price.tpl" value=$shipping.rate}</option>
                    {/foreach}
                    </select>
                </div>
            {elseif $group.shipping_no_required}
                {__("no_shipping_required")}
            {else}
                {__("text_no_shipping_methods")}
                {assign var="is_empty_rates" value="Y"}
            {/if}
            </div>
        {/foreach}
    {else}
        <span class="error-text">{__("text_no_shipping_methods")}</span>
    {/if}
{/hook}