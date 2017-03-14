<form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="new_payout_form">
<input type="hidden" name="redirect_url" value="{$c_url}" />

{include file="views/companies/components/company_field.tpl"
    name="payment[vendor]"
    id="p_vendor"
    selected=$smarty.request.vendor
}

<div class="control-group">
    <label class="cm-required control-label" for="payment_amount">{__("payment_amount")}</label>
    <div class="controls">
        <input type="text" class="cm-numeric" name="payment[amount]" id="payment_amount" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="payment_comments">{__("comments")}</label>
    <div class="controls">
    <textarea class="span9" rows="8" cols="55" name="payment[comments]" id="payment_comments"
    ></textarea></div>
</div>

{if !$runtime.company_id}
    <div class="control-group">
        <label for="" class="control-label">&nbsp;</label>
        <div class="controls cm-toggle-button">
            <div class="select-field notify-customer">
                <label class="checkbox" for="notify_user">
                    <input type="checkbox"
                           name="payment[notify_user]"
                           id="notify_user"
                           value="Y"
                    />
                    {__("notify_vendor")}
                </label>
            </div>
        </div>
    </div>
{/if}

<div class="buttons-container">
    {include file="buttons/save_cancel.tpl" but_name="dispatch[companies.payouts_add]" cancel_action="close"}
</div>

</form>