{script src="js/lib/jqueryuitouch/jquery.ui.touch-punch.min.js"}
{$min = $filter.min}
{$max = $filter.max}
{$left = $filter.left|default:$min}
{$right = $filter.right|default:$max}

{if ($max - $min <= $filter.round_to && $max - $min >= 0)}
    {$max = $min + $filter.round_to}
    {$disable_slider = true}
{/if}

<div id="content_{$filter_uid}" class="cm-product-filters-checkbox-container ty-price-slider {if $collapse}hidden{/if} {$extra_class}">
    {$filter.prefix nofilter}
    <input type="text" class="ty-price-slider__input-text" id="slider_{$filter_uid}_left" name="left_{$filter_uid}" value="{$left}"{if $disable_slider} disabled="disabled"{/if} />
    {$filter.suffix nofilter}
    &nbsp;–&nbsp;
    {$filter.prefix nofilter}
    <input type="text" class="ty-price-slider__input-text" id="slider_{$filter_uid}_right" name="right_{$filter_uid}" value="{$right}"{if $disable_slider} disabled="disabled"{/if} />
    {$filter.suffix nofilter}

    <div id="slider_{$filter_uid}" class="ty-range-slider cm-range-slider">
        <ul class="ty-range-slider__wrapper">
            <li class="ty-range-slider__item" style="left: 0%;"><span class="ty-range-slider__num">{$filter.prefix nofilter}{$min}{$filter.suffix nofilter}</span></li>
            <li class="ty-range-slider__item" style="left: 100%;"><span class="ty-range-slider__num">{$filter.prefix nofilter}{$max}{$filter.suffix nofilter}</span></li>
        </ul>
    </div>

    <input id="elm_checkbox_slider_{$filter_uid}" data-ca-filter-id="{$filter.filter_id}" class="cm-product-filters-checkbox hidden" type="checkbox" name="product_filters[{$filter.filter_id}]" value="" />

    {if ((string) $right == (string) $left && $right - $left >= 0)}
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
