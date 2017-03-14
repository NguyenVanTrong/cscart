{include file="common/pagination.tpl" search=$features_search div_id="product_features_pagination_`$product_id`" current_url="products.get_features?product_id=`$product_id`&multiple=1&over=$over&data_name=$data_name&items_per_page=`$features_search.items_per_page`"|fn_url disable_history=true}

<table>
{foreach from=$product_features item="pf" key="feature_id"}
{if $pf.feature_type != "ProductFeatures::GROUP"|enum}
<tr>

    {if $over == true}
    <td><label class="checkbox" for="elements-switcher-{$field}__{$pf.feature_id}_"><input type="checkbox" id="elements-switcher-{$field}__{$pf.feature_id}_" />&nbsp;{$pf.description}:&nbsp;</label></td>
    {else}
    <td>{$pf.description}:</td>
    {/if}

    <td >
        {include file="views/products/components/products_m_update_feature.tpl" feature=$pf pid=$product_id}
    </td>
</tr>
{/if}
{/foreach}
{foreach from=$product_features item="pf" key="feature_id"}
{if $pf.feature_type == "ProductFeatures::GROUP"|enum && $pf.subfeatures}
<tr>
    <td colspan="2"><span>{$pf.description}</span></td>
</tr>
{foreach from=$pf.subfeatures item=subfeature}
<tr>

    {if $over == true}
    <td class="nowrap"><label class="checkbox" for="elements-switcher-{$field}__{$subfeature.feature_id}_"><input type="checkbox" id="elements-switcher-{$field}__{$subfeature.feature_id}_"/>&nbsp;{$subfeature.description}</label></td>
    {else}
    <td>{$subfeature.description}:</td>
    {/if}

    <td>{include file="views/products/components/products_m_update_feature.tpl" feature=$subfeature pid=$product_id}</td>
</tr>
{/foreach}
{/if}
{/foreach}
</table>

{include file="common/pagination.tpl" search=$features_search div_id="product_features_pagination_`$product_id`" current_url="products.get_features?product_id=`$product_id`&multiple=1&over=$over&data_name=$data_name&items_per_page=`$features_search.items_per_page`"|fn_url disable_history=true}