{** block-description:selected_filters **}

{if $items}

{$ajax_div_ids = "product_filters_*,products_search_*,category_products_*,product_features_*,breadcrumbs_*,currencies_*,languages_*,selected_filters_*"}
{$curl = $config.current_url}
{$filter_base_url = $curl|fn_query_remove:"result_ids":"full_render":"filter_id":"view_all":"req_range_id":"features_hash":"subcats":"page":"total"}

<div class="ty-horizontal-product-filters ty-selected-product-filters cm-product-filters cm-horizontal-filters" id="selected_filters_{$block.block_id}">
<div class="ty-product-filters__wrapper">
{foreach from=$items item="filter"}

    {if $filter.selected_variants || $filter.selected_range}

    {$reset_url = $filter_base_url}
    {$fh = $smarty.request.features_hash|fn_delete_filter_from_hash:$filter.filter_id}
    {if $fh}
        {$reset_url = $filter_base_url|fn_link_attach:"features_hash=$fh"}
    {/if}

    <div class="ty-horizontal-product-filters-dropdown{if $filter.selected_variants || $filter.selected_range} active{/if}">
        <div id="sw_elm_selected_filter_{$filter.filter_id}" class="ty-horizontal-product-filters-dropdown__wrapper cm-combination">{$filter.filter}{if $filter.selected_variants} ({$filter.selected_variants|sizeof}){/if}<a class="cm-ajax cm-ajax-full-render cm-history" href="{$reset_url|fn_url}" data-ca-event="ce.filtersinit" data-ca-target-id="{$ajax_div_ids}"><i class="ty-icon-cancel-circle"></i></a><i class="ty-horizontal-product-filters-dropdown__icon ty-icon-down-micro"></i></div>
        <div id="elm_selected_filter_{$filter.filter_id}" class="cm-popup-box hidden ty-horizontal-product-filters-dropdown__content cm-horizontal-filters-content">
            {$filter_uid="`$block.block_id`_`$filter.filter_id`"}
            {if $filter.slider}
                {if $filter.feature_type == "ProductFeatures::DATE"|enum}
                    {include file="blocks/product_filters/components/product_filter_datepicker.tpl" filter_uid=$filter_uid filter=$filter}
                {else}
                    {include file="blocks/product_filters/components/product_filter_slider.tpl" filter_uid=$filter_uid filter=$filter}
                {/if}
            {else}
                {include file="blocks/product_filters/components/product_filter_variants.tpl" filter_uid=$filter_uid filter=$filter}
            {/if}
            <div class="ty-product-filters__tools clearfix">
                {include file="buttons/button.tpl" but_text=__("close") but_meta="cm-external-click" but_external_click_id="sw_elm_filter_`$filter.filter_id`"}
            </div>
        </div>
    </div>
    {/if}

{/foreach}
</div>
<!--selected_filters_{$block.block_id}--></div>

{/if}
