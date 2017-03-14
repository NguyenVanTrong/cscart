{if $variants}
{$size = 4}
{split data=$variants size=$size assign="splitted_filter" preverse_keys=true}

<div class="ty-features-all">
{foreach from=$splitted_filter item="group"}
    {foreach from=$group item="ranges" key="index"}
    {strip}
    <div class="ty-features-all__group ty-column6">
            {if $ranges}
                {include file="common/subheader.tpl" title=$index}
                <ul class="ty-features-all__list">
                {foreach from=$ranges item="range"}
                    <li class="ty-features-all__list-item"><a href="{"product_features.view?variant_id=`$range.variant_id`"|fn_url}" class="ty-features-all__list-a">{$range.variant|fn_text_placeholders}</a></li>
                {/foreach}
            </ul>
            {else}&nbsp;{/if}
    </div>
    {strip}
    {/foreach}
{/foreach}
</div>
{/if}