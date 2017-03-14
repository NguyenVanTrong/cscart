
<select id="{$tag_id}" name="{$tag_name}" class="select_ebay_product_identifier">
    {foreach from=$variants key=variant_key item=variant_name}
        {$selected = false}

        {if $value == $variant_key}
            {$selected = true}
        {elseif $value > 0 && $variant_key == 'feature'}
            {$selected = true}
        {/if}

        <option {if $selected}selected {/if}value="{$variant_key}">{$variant_name}</option>
    {/foreach}
</select>
{if isset($variants['feature'])}
    <select id="feature_{$tag_id}" name="{$tag_name}" class="hidden" disabled>
        <option value="{$value}"></option>
    </select>
{/if}