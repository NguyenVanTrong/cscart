<ul class="product-filters {if $collapse}hidden{/if}" id="content_{$filter_uid}">

    {if $filter.display_count && $filter.variants|count > $filter.display_count}
    <li>
        {script src="js/tygh/filter_table.js"}

        <div class="product-filters-search">
        <input type="text" placeholder="{__("search")}" class="cm-autocomplete-off input-text-medium" name="q" id="elm_search_{$filter_uid}" value="" />
        <i class="product-filters-search-icon icon-cancel-circle hidden" id="elm_search_clear_{$filter_uid}" title="{__("clear")}"></i>
        </div>
    </li>
    {/if}

    {* Selected variants *}
    {foreach from=$filter.selected_variants key="variant_id" item="variant"}
        <li class="cm-product-filters-checkbox-container">
            <label><input class="cm-product-filters-checkbox" type="checkbox" name="product_filters[{$filter.filter_id}]" data-ca-filter-id="{$filter.filter_id}" value="{$variant.variant_id}" id="elm_checkbox_{$filter_uid}_{$variant.variant_id}" checked="checked">{$filter.prefix}{$variant.variant|fn_text_placeholders}{$filter.suffix}</label>
        </li>
    {/foreach}

    {if $filter.variants}
        <li>
            <ul id="ranges_{$filter_uid}" {if $filter.display_count}style="max-height: {$filter.display_count * 2}em;"{/if} class="product-filters-variants cm-filter-table" data-ca-input-id="elm_search_{$filter_uid}" data-ca-clear-id="elm_search_clear_{$filter_uid}" data-ca-empty-id="elm_search_empty_{$filter_uid}">

                {foreach from=$filter.variants item="variant"}
                <li class="cm-product-filters-checkbox-container">
                    <label {if $variant.disabled}class="disabled"{/if}><input class="cm-product-filters-checkbox" type="checkbox" name="product_filters[{$filter.filter_id}]" data-ca-filter-id="{$filter.filter_id}" id="elm_checkbox_{$filter_uid}_{$variant.variant_id}" value="{$variant.variant_id}" {if $variant.disabled}disabled="disabled"{/if}>{$filter.prefix}{$variant.variant|fn_text_placeholders}{$filter.suffix}</label>
                </li>
                {/foreach}
            </ul>
            <p id="elm_search_empty_{$filter_uid}" class="hidden">{__("no_items_found")}</p>
        </li>
    {/if}
</ul>
