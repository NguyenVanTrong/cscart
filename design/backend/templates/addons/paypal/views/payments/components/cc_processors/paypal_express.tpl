{assign var="paypal_currencies" value="express"|fn_paypal_get_currencies}

<p>{__("paypal_express_notice")}</p>

{include file="addons/paypal/common/connect_to_paypal.tpl"}

<hr>

<div class="control-group">
    <label class="control-label" for="currency">{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="currency">
            {foreach from=$paypal_currencies item="currency"}
                <option value="{$currency.code}"{if !$currency.active} disabled="disabled"{/if}{if $processor_params.currency == $currency.code} selected="selected"{/if}>{$currency.name}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="order_prefix">{__("order_prefix")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][order_prefix]" id="order_prefix" size="36" value="{$processor_params.order_prefix}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="elm_in_context">{__("paypal_use_in_context_checkout")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][in_context]" value="N" />
        <input type="checkbox" name="payment_data[processor_params][in_context]" {if $processor_params.in_context|default:"Y" == "Y"}checked="checked"{/if} id="elm_in_context" value="Y" />
    </div>
</div>

{include file="common/subheader.tpl" title=__("addons.paypal.technical_details") target="#section_technical_details"}

<div id="section_technical_details">

    <div class="control-group">
        <label class="control-label{if $processor_params.in_context|default:"Y" == "Y"} cm-required{/if}" for="elm_merchant_id" id="lbl_merchant_id">{__("merchant_id")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][merchant_id]" id="elm_merchant_id" size="24" value="{$processor_params.merchant_id}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="username">{__("username")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][username]" id="username" size="24" value="{$processor_params.username}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="password">{__("password")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][password]" id="password" size="24" value="{$processor_params.password}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label">{__("paypal_authentication_method")}:</label>
        <div class="controls">
            <label class="radio inline" for="elm_payment_auth_method_cert">
                <input id="elm_payment_auth_method_cert" type="radio" value="cert" name="payment_data[processor_params][authentication_method]" {if $processor_params.authentication_method == "cert" || !$processor_params.authentication_method} checked="checked"{/if}>
                {__("certificate")}
            </label>

            <label class="radio inline" for="elm_payment_auth_method_signature">
                <input id="elm_payment_auth_method_signature" type="radio" value="signature" name="payment_data[processor_params][authentication_method]" {if $processor_params.authentication_method == "signature"} checked="checked"{/if}>
                {__("signature")}
            </label>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="certificate">{__("certificate_filename")}:</label>
        <div class="controls" id="certificate_file">

            {if $processor_params.certificate_filename}
                <div class="text-type-value pull-left">
                    {$processor_params.certificate_filename}
                    <a href="{'payments.delete_certificate?payment_id='|cat:$payment_id|fn_url}" class="cm-ajax cm-post" data-ca-target-id="certificate_file">
                        <i class="icon-remove-sign cm-tooltip hand" title="{__('remove')}"></i>
                    </a>
                </div>
            {/if}

            <div {if $processor_params.certificate_filename}class="clear"{/if}>{include file="common/fileuploader.tpl" var_name="payment_certificate[]"}</div>
        <!--certificate_file--></div>
    </div>

    <div class="control-group">
        <label class="control-label" for="signature">{__("signature")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][signature]" id="signature" value="{$processor_params.signature}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="send_adress">{__("send_shipping_address")}:</label>
        <div class="controls">
            <input type="checkbox" name="payment_data[processor_params][send_adress]" {if !$payment_id || $processor_params.send_adress == "Y"}checked="checked"{/if} id="send_adress" value="Y">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="mode">{__("test_live_mode")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][mode]" id="mode">
                <option value="test" {if $processor_params.mode eq "test"} selected="selected"{/if}>{__("test")}</option>
                <option value="live" {if $processor_params.mode eq "live"} selected="selected"{/if}>{__("live")}</option>
            </select>
        </div>
    </div>
</div>

<script type="text/javascript">
    (function(_, $) {
        $(document).ready(function() {
            $('#elm_in_context').on('change', function() {
                $('#lbl_merchant_id').toggleClass('cm-required', $(this).is(':checked'));
            });
        });
    })(Tygh, Tygh.$);
</script>