{capture name="checkout_sign_in_login"}
{hook name="checkout:login_form"}
    <div class="login-form float-left">
        {include file="views/auth/login_form.tpl" id="checkout_login" style="checkout" result_ids="checkout*,account*"}
    </div>
{/hook}
{/capture}

{capture name="checkout_sign_in_register"}
{hook name="checkout:register_customer"}
    <div class="checkout-register">
        {capture name="register"}
            {if $settings.General.approve_user_profiles != "Y"}
                <div id="register_checkout" class="checkout-buttons{if $settings.Checkout.sign_in_default_action != "register"} cm-noscript{/if}">
                    {include file="buttons/button.tpl" but_onclick="fn_switch_checkout_type();" but_text=__("register")}
                </div>
            {/if}
        {/capture}
        
        {capture name="anonymous"}
            {if $settings.Checkout.disable_anonymous_checkout != "Y"}
                <div id="anonymous_checkout" class="{if $settings.Checkout.sign_in_default_action == "register"} cm-noscript{/if}">
                    <form name="step_one_anonymous_checkout_form" class="{$ajax_form}" action="{""|fn_url}" method="post">
                        <input type="hidden" name="result_ids" value="checkout*,account*" />
                        <input type="hidden" name="guest_checkout" value="1" />

                        {if !$contact_fields_filled}
                            <div class="control-group">
                                <label for="guest_email" class="cm-required">{__("email")}</label>
                                <input type="text" id="guest_email" name="user_data[email]" size="32" value="" class="input-text " />
                            </div>
                        {/if}

                        <div class="checkout-buttons">
                            {include file="buttons/button.tpl" but_name="dispatch[checkout.customer_info]" but_text=__("checkout_as_guest")}
                        </div>
                    </form>
                </div>
            {/if}
        {/capture}

        <div class="register-content">
            {if $settings.General.approve_user_profiles != "Y" || $settings.Checkout.disable_anonymous_checkout != "Y"}
                {include file="common/subheader.tpl" title=__("new_customer")}
            {/if}

            <ul class="register-methods">
                {capture name="checkout_new_customer_register"}
                <li class="one">
                    <input class="radio valign" type="radio" id="checkout_type_register" name="checkout_type" value=""{if $settings.Checkout.sign_in_default_action == "register"} checked="checked"{/if} onclick="fn_show_checkout_buttons('register')" />
                    <div class="radio1">
                        <label for="checkout_type_register">
                            <span class="method-title">{__("register")}</span>
                            <span class="method-hint">{__("create_new_account")}</span>
                        </label>
                    </div>
                </li>
                {/capture}

                {capture name="checkout_new_customer_guest"}
                {if $settings.Checkout.disable_anonymous_checkout != "Y"}
                    <li>
                        <input class="radio valign" type="radio" id="checkout_type_guest" name="checkout_type" value=""{if $settings.Checkout.sign_in_default_action != "register"} checked="checked"{/if} onclick="fn_show_checkout_buttons('guest')" />
                        <div class="radio1">
                            <label for="checkout_type_guest">
                                <span class="method-title">{__("checkout_as_guest")}</span>
                                <span class="method-hint">{__("create_guest_account")}</span>
                            </label>
                        </div>
                    </li>
                {/if}
                {/capture}

                {if $settings.Checkout.sign_in_default_action == "register"}
                    {$smarty.capture.checkout_new_customer_register nofilter}
                    {$smarty.capture.checkout_new_customer_guest nofilter}
                {else}
                    {$smarty.capture.checkout_new_customer_guest nofilter}
                    {$smarty.capture.checkout_new_customer_register nofilter}
                {/if}
            </ul>
        </div>

        {$smarty.capture.register nofilter}
        {$smarty.capture.anonymous nofilter}
    </div>
{/hook}
{/capture}

{if $settings.Checkout.configure_sign_in_step == "returning_customer_first"}
    {$smarty.capture.checkout_sign_in_login nofilter}
    <div class="checkout-separator"></div>
    {$smarty.capture.checkout_sign_in_register nofilter}
{else}
    {$smarty.capture.checkout_sign_in_register nofilter}
    <div class="checkout-separator"></div>
    {$smarty.capture.checkout_sign_in_login nofilter}
{/if}
