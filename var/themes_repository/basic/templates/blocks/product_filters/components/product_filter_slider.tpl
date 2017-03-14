{$min = $filter.min}
{$max = $filter.max}
{$left = $filter.left|default:$min}
{$right = $filter.right|default:$max}

{if $max - $min <= $filter.round_to}
    {$disable_slider = true}
{/if}

<div id="content_{$filter_uid}" class="cm-product-filters-checkbox-container price-slider {$extra_class}{if $collapse} hidden{/if}">
    {$filter.prefix nofilter}<input type="text" class="input-text" id="slider_{$filter_uid}_left" name="left_{$filter_uid}" value="{$left}"{if $disable_slider} disabled="disabled"{/if} />{$filter.suffix nofilter}
    &nbsp;–&nbsp;
    {$filter.prefix nofilter}<input type="text" class="input-text" id="slider_{$filter_uid}_right" name="right_{$filter_uid}" value="{$right}"{if $disable_slider} disabled="disabled"{/if} />{$filter.suffix nofilter}

    <div id="slider_{$filter_uid}" class="range-slider cm-range-slider">
        <ul>
            <li style="left: 0%;"><i><b>{$filter.prefix nofilter}{$min}{$filter.suffix nofilter}</b></i></li>
            <li style="left: 100%;"><i><b>{$filter.prefix nofilter}{$max}{$filter.suffix nofilter}</b></i></li>
        </ul>
    </div>

    <input id="elm_checkbox_slider_{$filter_uid}" data-ca-filter-id="{$filter.filter_id}" class="cm-product-filters-checkbox hidden" type="checkbox" name="product_filters[{$filter.filter_id}]" value="" />

    {if $right == $left}
        {$right = $left + $filter.round_to}
    {/if}

    {* Slider params *}
    <input type="hidden" id="slider_{$filter_uid}_json" value='{ldelim}
        "disabled": {$disable_slider|default:"false"},
        "min": {$min},
        "max": {$max},
        "left": {$left},
        "right": {$right},
        "step": {$filter.round_to},
        "extra": "{$filter.extra}"
    {rdelim}' />
    {* /Slider params *}

</div>
