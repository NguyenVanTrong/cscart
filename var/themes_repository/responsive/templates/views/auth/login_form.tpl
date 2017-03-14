{assign var="id" value=$id|default:"main_login"}

{capture name="login"}
    <form name="{$id}_form" action="{""|fn_url}" method="post">
    <input type="hidden" name="return_url" value="{$smarty.request.return_url|default:$config.current_url}" />
    <input type="hidden" name="redirect_url" value="{$config.current_url}" />

        {if $style == "checkout"}
            <div class="ty-checkout-login-form">{include file="common/subheader.tpl" title=__("returning_customer")}
        {/if}
        <div class="ty-control-group">
            <label for="login_{$id}" class="ty-login__filed-label ty-control-group__label cm-required cm-trim cm-email">{__("email")}</label>
            <input type="text" id="login_{$id}" name="user_login" size="30" value="{$config.demo_username}" class="ty-login__input cm-focus" />
        </div>

        <div class="ty-control-group ty-password-forgot">
            <label for="psw_{$id}" class="ty-login__filed-label ty-control-group__label ty-password-forgot__label cm-required">{__("password")}</label><a href="{"auth.recover_password"|fn_url}" class="ty-password-forgot__a"  tabindex="5">{__("forgot_password_question")}</a>
            <input type="password" id="psw_{$id}" name="password" size="30" value="{$config.demo_password}" class="ty-login__input" maxlength="32" />
        </div>

        {if $style == "popup"}
            <div class="ty-login-reglink ty-center">
                <a class="ty-login-reglink__a" href="{"profiles.add"|fn_url}" rel="nofollow">{__("register_new_account")}</a>
            </div>
        {/if}

        {include file="common/image_verification.tpl" option="login" align="left"}

        {if $style == "checkout"}
            </div>
        {/if}

        {hook name="index:login_buttons"}
            <div class="buttons-container clearfix">
                <div class="ty-float-right">
                    {include file="buttons/login.tpl" but_name="dispatch[auth.login]" but_role="submit"}
                </div>
                <div class="ty-login__remember-me">
                    <label for="remember_me_{$id}" class="ty-login__remember-me-label"><input class="checkbox" type="checkbox" name="remember_me" id="remember_me_{$id}" value="Y" />{__("remember_me")}</label>
                </div>
            </div>
        {/hook}
    </form>
{/capture}

{if $style == "popup"}
    {$smarty.capture.login nofilter}
{else}
    <div class="ty-login">
        {$smarty.capture.login nofilter}
    </div>

    {capture name="mainbox_title"}{__("sign_in")}{/capture}
{/if}
