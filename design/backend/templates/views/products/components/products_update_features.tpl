<div id="content_features" class="hidden">

{if $product_features}

{include file="common/pagination.tpl" search=$features_search div_id="product_features_pagination_`$product_id`" current_url="products.get_features?product_id=`$product_id`&items_per_page=`$features_search.items_per_page`"|fn_url disable_history=true}

<fieldset>
    {include file="views/products/components/product_assign_features.tpl" product_features=$product_features}
</fieldset>

{include file="common/pagination.tpl" search=$features_search div_id="product_features_pagination_`$product_id`" current_url="products.get_features?product_id=`$product_id`&items_per_page=`$features_search.items_per_page`"|fn_url disable_history=true}

{else}
<p class="no-items">{__("no_items")}</p>
{/if}
</div>