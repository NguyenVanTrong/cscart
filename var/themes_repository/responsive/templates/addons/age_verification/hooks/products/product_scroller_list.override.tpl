{if !$smarty.session.auth.age && $product.need_age_verification == "Y"}
<div class="ty-age-verification ty-scroller-list__item">

    <div class="ty-scroller-list__description">
        {strip}
            {include file="blocks/list_templates/simple_list.tpl" product=$product show_name=true show_price=false show_add_to_cart=false but_role="action" hide_price=true hide_qty=true show_discount_label=false}
        {/strip}
    </div>

    <div class="ty-mt-m">
        <div class="ty-age-verification__txt">{__("product_need_age_verification")}</div>
        <div class="buttons-container">
            {include file="buttons/button.tpl" but_text=__("verify") but_href="products.view?product_id=`$product.product_id`" but_meta="ty-btn__secondary" but_role="text"}
        </div>
    </div>
</div>
{/if}