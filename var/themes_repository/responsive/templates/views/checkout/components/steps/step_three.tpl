{$show_number_of_steps = !$complete || $edit || $final_step == "step_three"}
<div class="ty-step__container{if $edit}-active{/if} ty-step-three" data-ct-checkout="shipping_options" id="step_three">
    <h3 class="ty-step__title{if $edit}-active{/if}{if !$show_number_of_steps}-complete{/if} clearfix">
        <span class="ty-step__title-left">{if $show_number_of_steps}{$number_of_step}{else}<i class="ty-step__title-icon ty-icon-ok"></i>{/if}</span>
        <i class="ty-step__title-arrow ty-icon-down-micro"></i>

        {if !$show_number_of_steps}
            {hook name="checkout:step_three_edit_link"}
            <span class="ty-step__title-right">
                {include file="buttons/button.tpl" but_meta="ty-btn__secondary cm-ajax" but_href="checkout.checkout?edit_step=step_three&from_step={$cart.edit_step}" but_target_id="checkout_*" but_text=__("change") but_role="tool"}
            </span>
            {/hook}
        {/if}

        {hook name="checkout:step_three_edit_link_title"}
        {if !$show_number_of_steps}
            <a class="ty-step__title-txt cm-ajax" href="{"checkout.checkout?edit_step=step_three&from_step={$cart.edit_step}"|fn_url}" data-ca-target-id="checkout_*">{__("shipping_options")}</a>
        {else}
            <span class="ty-step__title-txt">{__("shipping_options")}</span>
        {/if}
        {/hook}
    </h3>

    <div id="step_three_body" class="ty-step__body{if $edit}-active{/if} {if !$edit}hidden{/if} clearfix">
        {if $edit}
            <form name="step_three_payment_and_shipping" class="{$ajax_form} cm-ajax-full-render" action="{""|fn_url}" method="{if !$edit}get{else}post{/if}">
                <input type="hidden" name="update_step" value="step_three" />
                <input type="hidden" name="next_step" value="{$next_step}" />
                <input type="hidden" name="result_ids" value="checkout*" />

                <div class="clearfix">
                    <div class="checkout__block">
                    {hook name="checkout:select_shipping"}
                        {if !$cart.shipping_failed}
                            {include file="views/checkout/components/shipping_rates.tpl" no_form=true }
                        {else}
                            <p class="ty-error-text">{__("text_no_shipping_methods")}</p>
                        {/if}
                    {/hook}

                    {if $edit}
                         <div class="ty-checkout__shipping-tips">
                             {__("shipping_tips")}
                         </div>
                    {/if}
                    </div>
                </div>

                {if $final_step == "step_three"}
                    {include file="views/checkout/components/final_section.tpl"}
                {else}
                    <div class="ty-checkout-buttons">
                        {include file="buttons/button.tpl" but_meta="ty-btn__secondary" but_name="dispatch[checkout.update_steps]" but_text=$but_text but_id="step_three_but"}
                    </div>
                {/if}

            </form>

        {/if}
    </div>
<!--step_three--></div>
