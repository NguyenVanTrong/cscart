{if $variants}
{$size = 4}
{split data=$variants size=$size assign="splitted_filter" preverse_keys=true}

{split data=$view_all_filter size="4" assign="splitted_filter" preverse_keys=true}
<table class="view-all table-width">
{foreach from=$splitted_filter item="group"}
<tr class="valign-top">
    {foreach from=$group item="ranges" key="index"}
    <td class="center" style="width: 25%">
        <div>
            {if $ranges}
                {include file="common/subheader.tpl" title=$index}
                <ul>
                {foreach from=$ranges item="range"}
                    <li><a href="{"product_features.view?variant_id=`$range.variant_id`"|fn_url}">{$range.variant|fn_text_placeholders}</a></li>
                {/foreach}
            </ul>
            {else}&nbsp;{/if}
        </div>
    </td>
    {/foreach}
</tr>
{/foreach}
</table>
{/if}