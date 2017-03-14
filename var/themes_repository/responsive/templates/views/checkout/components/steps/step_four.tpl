{script src="js/tygh/tabs.js"}

<div class="ty-step__container{if $edit}-active{/if} ty-step-four" data-ct-checkout="billing_options" id="step_four">
    <h3 class="ty-step__title{if $edit}-active{/if} clearfix">
        <span class="ty-step__title-left">{$number_of_step}</span>
        <i class="ty-step__title-arrow ty-icon-down-micro"></i>
        
        {hook name="checkout:step_four_edit_link_title"}
        {if $complete && !$edit}
            <a class="ty-step__title-txt cm-ajax" href="{"checkout.checkout?edit_step=step_four&from_step={$cart.edit_step}"|fn_url}" data-ca-target-id="checkout_*">{__("billing_options")}</a>
        {else}
            <span class="ty-step__title-txt">{__("billing_options")}</span>
        {/if}
        {/hook}
    </h3>

    <div id="step_four_body" class="ty-step__body{if $edit}-active{/if} {if !$edit}hidden{/if}">
        <div class="clearfix ty-checkout__billing-tabs">
            
            {if $edit}
                {if $cart|fn_allow_place_order:$auth}
                    {if $cart.payment_id}
                        <div class="clearfix">
                            {include file="views/checkout/components/payments/payment_methods.tpl" payment_id=$cart.payment_id}
                        </div>
                    {else}
                        <div class="checkout__block"><h3 class="ty-subheader">{__("text_no_payments_needed")}</h3></div>
                        
                        <form name="paymens_form" action="{""|fn_url}" method="post">
                            {include file="views/checkout/components/final_section.tpl" is_payment_step=true}
                            <div class="ty-checkout-buttons">
                                {include file="buttons/place_order.tpl" but_text=__("submit_my_order") but_name="dispatch[checkout.place_order]" but_id="place_order"}
                            </div>
                        </form>
                    {/if}
                {else}
                    {include file="views/checkout/components/final_section.tpl" is_payment_step=true}
                {/if}
            {/if}

        </div>
    </div>
<!--step_four--></div>

<div id="place_order_data" class="hidden">
</div>