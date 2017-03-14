{if !$smarty.session.auth.age && $product.need_age_verification == "Y"}
    {assign var="obj_id" value=$product.product_id}    
    <div class="ty-age-verification__block ty-thumbnail-list__item">

        <div class="ty-thumbnail-list__name">
            {assign var="name" value="name_$obj_id"}{$smarty.capture.$name nofilter}
        </div>

        <div class="ty-mt-m">
            <div class="ty-age-verification__txt">{__("product_need_age_verification")}</div>
            <div class="buttons-container">
                {include file="buttons/button.tpl" but_text=__("verify") but_href="products.view?product_id=`$product.product_id`" but_meta="ty-btn__secondary" but_role="text"}
            </div>
        </div>
    </div>
{/if}