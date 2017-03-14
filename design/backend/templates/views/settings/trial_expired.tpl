{if $show}
    <a id="trial" class="cm-dialog-opener cm-dialog-auto-size hidden cm-dialog-non-closable" data-ca-target-id="trial_dialog"></a>

    <div class="hidden trial-expired-dialog" title="{__("trial_expired", ["[product]" => $smarty.const.PRODUCT_NAME])}" id="trial_dialog">
        {if $store_mode_errors}
            <div class="alert alert-error notification-content">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {foreach from=$store_mode_errors item="message"}
                    <strong>{$message.title}:</strong> {$message.text nofilter}<br>
                {/foreach}
            </div>
        {/if}

        <form name="trial_form" id="trial_dialog_form" action="{""|fn_url}" method="post">
            <input type="hidden" name="redirect_url" value="{$config.current_url}">
            <input type="hidden" name="store_mode" value="full">
            <div class="trial-expired">
                <p>{__("text_input_license_code", ["[product]" => $smarty.const.PRODUCT_NAME])}</p>

                <div class="license {if $store_mode_errors} type-error{/if} item">
                    <input type="text" name="license_number" class="{if $store_mode_errors} type-error{/if}" value="{$store_mode_license}" placeholder="{__("please_enter_license_here")}">
                    <input name="dispatch[settings.change_store_mode]" type="submit" value="{__("activate")}" class="btn btn-primary">
                </div>
                <div class="trial-purchase">
                    <p>
                        {__("text_buy_new_license")}
                    </p>
                        <a class="btn btn-warning btn-large btn-buy" target="_blank" href="{$config.resources.product_buy_url}">{__("buy_license")}</a>
                    <p>
                        {__("text_money_back_guarantee")}
                    </p>
                </div>
            </div>
        </form>
    </div>

    <script type="text/javascript">
        Tygh.$(document).ready(function () {
            Tygh.$('#trial').trigger('click');
        });

        Tygh.$(window).load(function () {
            Tygh.$('#trial_dialog_form').off('submit');
        });
    </script>
{/if}