{if $products}

    {script src="js/tygh/exceptions.js"}

    {if !$no_pagination}
        {include file="common/pagination.tpl"}
    {/if}

    {if !$no_sorting}
        {include file="views/products/components/sorting.tpl"}
    {/if}

    {assign var="image_width" value=$image_width|default:60}
    {assign var="image_height" value=$image_height|default:60}

    <div class="ty-compact-list">
        {foreach from=$products item="product" key="key" name="products"}
            {assign var="obj_id" value=$product.product_id}
            {assign var="obj_id_prefix" value="`$obj_prefix``$product.product_id`"}
            {include file="common/product_data.tpl" product=$product}
            {hook name="products:product_compact_list"}
                <div class="ty-compact-list__item">
                    <form {if !$config.tweaks.disable_dhtml}class="cm-ajax cm-ajax-full-render"{/if} action="{""|fn_url}" method="post" name="short_list_form{$obj_prefix}">
                        <input type="hidden" name="result_ids" value="cart_status*,wish_list*,account_info*" />
                        <input type="hidden" name="redirect_url" value="{$config.current_url}" />
                        <div class="ty-compact-list__content">
                            <div class="ty-compact-list__image">
                                <a href="{"products.view?product_id=`$product.product_id`"|fn_url}">
                                    {include file="common/image.tpl" image_width=$image_width image_height=$image_height images=$product.main_pair obj_id=$obj_id_prefix}
                                </a>
                                {assign var="discount_label" value="discount_label_`$obj_prefix``$obj_id`"}
                                {$smarty.capture.$discount_label nofilter}
                            </div>
                            
                            <div class="ty-compact-list__title">
                                {assign var="name" value="name_$obj_id"}{$smarty.capture.$name nofilter}

                                {$sku = "sku_`$obj_id`"}
                                {$smarty.capture.$sku nofilter}

                            </div>

                            <div class="ty-compact-list__controls">
                                <div class="ty-compact-list__price">
                                    {assign var="old_price" value="old_price_`$obj_id`"}
                                    {if $smarty.capture.$old_price|trim}
                                        {$smarty.capture.$old_price nofilter}
                                    {/if}

                                    {assign var="price" value="price_`$obj_id`"}
                                    {$smarty.capture.$price nofilter}

                                    {assign var="clean_price" value="clean_price_`$obj_id`"}
                                    {$smarty.capture.$clean_price nofilter}
                                </div>

                                {if !$smarty.capture.capt_options_vs_qty}
                                    {assign var="product_options" value="product_options_`$obj_id`"}
                                    {$smarty.capture.$product_options nofilter}

                                    {assign var="qty" value="qty_`$obj_id`"}
                                    {$smarty.capture.$qty nofilter}
                                {/if}

                                {if $show_add_to_cart}
                                    {assign var="add_to_cart" value="add_to_cart_`$obj_id`"}
                                    {$smarty.capture.$add_to_cart nofilter}
                                {/if}
                            </div>
                        </div>
                    </form>
                </div>
            {/hook}
        {/foreach}
    </div>

{if !$no_pagination}
    {include file="common/pagination.tpl" force_ajax=$force_ajax}
{/if}

{/if}