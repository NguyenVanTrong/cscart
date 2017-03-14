{** block-description:horizontal_filters **}

{script src="js/tygh/product_filters.js"}

{if $block.type == "product_filters"}
    {$ajax_div_ids = "product_filters_*,products_search_*,category_products_*,product_features_*,breadcrumbs_*,currencies_*,languages_*,selected_filters_*"}
    {$curl = $config.current_url}
{else}
    {$curl = "products.search"|fn_url}
    {$ajax_div_ids = ""}
{/if}

{$filter_base_url = $curl|fn_query_remove:"result_ids":"full_render":"filter_id":"view_all":"req_range_id":"features_hash":"subcats":"page":"total"}

<div class="horizontal-product-filters cm-product-filters cm-horizontal-filters" data-ca-target-id="{$ajax_div_ids}" data-ca-base-url="{$filter_base_url|fn_url}" id="product_filters_{$block.block_id}">
<div class="product-filters__wrapper">
{if $items}

{foreach from=$items item="filter" name="filters"}

    {$filter_uid = "`$block.block_id`_`$filter.filter_id`"}

    {$reset_url = ""}
    {if $filter.selected_variants || $filter.selected_range}
        {$reset_url = $filter_base_url}
        {$fh = $smarty.request.features_hash|fn_delete_filter_from_hash:$filter.filter_id}
        {if $fh}
            {$reset_url = $filter_base_url|fn_link_attach:"features_hash=$fh"}
        {/if}
    {/if}

    <div class="dropdown-container">
        <span id="sw_elm_filter_{$filter.filter_id}" class="sort-dropdown cm-combination"><a>{$filter.filter}{if $filter.selected_variants} ({$filter.selected_variants|sizeof}){/if}</a>{if $reset_url}<a class="icon-cancel-circle cm-ajax cm-ajax-full-render cm-history" href="{$reset_url|fn_url}" data-ca-event="ce.filtersinit" data-ca-target-id="{$ajax_div_ids}" data-ca-scroll=".cm-pagination-container"></a>{/if}<i class="icon-down-micro"></i></span>
        <div id="elm_filter_{$filter.filter_id}" class="cm-popup-box hidden dropdown-content filters-dropdown-content">
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
            <div class="filters-tools clearfix">
                {include file="buttons/button.tpl" but_text=__("show") but_meta="cm-external-click" but_external_click_id="sw_elm_filter_`$filter.filter_id`"}
            </div>
        </div>
    </div>

{/foreach}

{/if}
</div>
<!--product_filters_{$block.block_id}--></div>