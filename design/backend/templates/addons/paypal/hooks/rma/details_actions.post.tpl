{if $is_refund == "Y" && $order_info.payment_method.processor_id|fn_is_paypal_processor}
<div class="control-group notify-department">
    <label class="control-label" for="elm_paypal_perform_refund">{__("addons.paypal.rma.perform_refund")}</label>
    <div class="controls">
        {if $return_info.return_id|fn_is_paypal_refund_performed}
            <p class="label label-success">{__("refunded")}</p>
        {else}
            <label class="checkbox">
                <input type="checkbox" name="change_return_status[paypal_perform_refund]" id="elm_paypal_perform_refund" value="Y" />
            </label>
        {/if}
    </div>
</div>
{/if}