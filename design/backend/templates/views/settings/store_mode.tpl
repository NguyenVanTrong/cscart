{if $show}
    <a id="store_mode" class="cm-dialog-opener cm-dialog-auto-size hidden cm-dialog-non-closable" data-ca-target-id="store_mode_dialog"></a>
{/if}

<div class="hidden" title="{__("store_mode")}" id="store_mode_dialog">
    {if $store_mode_errors}
        <div class="alert alert-error notification-content">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {foreach from=$store_mode_errors item="message"}
            <strong>{$message.title}:</strong> {$message.text nofilter}<br>
        {/foreach}
        </div>
    {/if}

    <form name="store_mode_form" action="{""|fn_url}" method="post">
    <input type="hidden" name="redirect_url" value="{$config.current_url}">
    
        <span class="choice-text">{__("choose_your_store_mode")}:</span>

            <ul class="store-mode inline">
                <li class="clickable {if $store_mode_errors} type-error{/if} item{if $store_mode != "trial"} active{/if}">
                    <label for="store_mode_radio_full" class="radio">
                        <input type="radio" id="store_mode_radio_full" name="store_mode" value="full" {if $store_mode != "trial"}checked="checked"{/if} class="cm-switch-class">{__("full")}</label>
                    <div>
                        {$description_suffix = $product_state_suffix}
                        {if $store_mode == "trial"}
                            {$description_suffix = "new"|fn_get_product_state_suffix}
                        {/if}
                        {__("product_state_description.`$description_suffix`", [
                            "[product]" => $smarty.const.PRODUCT_NAME,
                            "[standard_license_url]" => $config.resources.standard_license_url,
                            "[ultimate_license_url]" => $config.resources.ultimate_license_url
                        ])}
                    </div>
                    <label>{__("license_number")}:</label>
                    <input type="text" name="license_number" class="{if $store_mode_errors} type-error{/if}" value="{$store_mode_license}" placeholder="{__("please_enter_license_here")}">
                    {if fn_allowed_for("ULTIMATE") && $store_mode_license && !$store_mode_errors && $store_mode != "trial"}
                        <p>
                            {__("licensed_product")}: {$store_mode|fn_get_licensed_mode_name}
                        </p>
                    {/if}
                </li>

                <li class="{if $store_mode == "trial"}active{elseif $store_mode != "new"}disabled{/if}">
                    <label for="store_mode_radio_trial" class="radio">
                        <input type="radio" id="store_mode_radio_trial" name="store_mode" value="trial" {if $store_mode == "trial"}checked="checked"{/if} {if $store_mode != "new" && $store_mode != "trial"}disabled="disabled"{/if}>{__("trial")}</label>
                    {if $store_mode != "new" && $store_mode != "trial"}
                        {if "ULTIMATE"|fn_allowed_for}
                            <div>{__("trial_mode_ult_disabled")}</div>
                        {else}
                            <div>{__("trial_mode_mve_disabled")}</div>
                        {/if}
                    {else}
                        <div>{__("text_store_mode_trial", ["[product_buy_url]" => $config.resources.product_buy_url])}</div>
                    {/if}
                </li>
            </ul>

        <div class="buttons-container">            
            <input name="dispatch[settings.change_store_mode]" type="submit" value="{__("select")}" class="btn btn-primary">
        </div>
    </form>
</div>

<script type="text/javascript">
Tygh.$(document).ready(function(){$ldelim}
    {if $show}
        Tygh.$('#store_mode').trigger('click');
    {/if}

    Tygh.$(document).on('click', '#store_mode_dialog li:not(.disabled)', function(){
        $('#store_mode_dialog li').removeClass('active');
        $(this).addClass('active').find('input[type="radio"]').prop('checked', true);
    });
{$rdelim});
</script>
