{if $product.extra.buy_together}
    <ul class="ty-buy-together-cart-items__list">
        {foreach from=$_cart_products item="_product" key="_key"}
            {if $_product.extra.parent.buy_together == $key}
                <li class="ty-buy-together-cart-items__list-item">
                    {if $block.properties.products_links_type == "thumb"}
                        <div class="ty-cart-items__list-item-image">
                            {include file="common/image.tpl" image_width="40" image_height="40" images=$_product.main_pair no_ids=true}
                        </div>
                    {/if}
                    <div class="ty-cart-items__list-item-desc">
                        <a href="{"products.view?product_id=`$_product.product_id`"|fn_url}"
                           class="ty-buy-together-cart__item-link">{$_product.product_id|fn_get_product_name}</a>
                        <p>
                            <span>{$_product.amount}</span><span>&nbsp;x&nbsp;</span>{include file="common/price.tpl" value=$_product.display_price span_id="price_`$key`_`$dropdown_id`" class="none"}</p>
                    </div>
                </li>
                {if $_product.product_option_data}
                    <li class="ty-buy-together-cart__item">{include file="common/options_info.tpl" product_options=$_product.product_option_data}</li>
                {/if}
            {/if}
        {/foreach}
    </ul>
{/if}