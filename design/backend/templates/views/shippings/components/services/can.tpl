{include file="common/subheader.tpl" title="{__("shippings.can.api_keys")}"}
<fieldset>
    <div class="control-group">
        <label class="control-label cm-required" for="elm_shipping_can_username">{__("shippings.can.username")}</label>
        <div class="controls">
            <input id="elm_shipping_can_username" type="text" name="shipping_data[service_params][username]" size="30" value="{$shipping.service_params.username}" />
        </div>
    </div>
    <div class="control-group">
        <label class="control-label cm-required" for="elm_shipping_can_password">{__("shippings.can.password")}</label>
        <div class="controls">
            <input id="elm_shipping_can_password" type="text" name="shipping_data[service_params][password]" size="30" value="{$shipping.service_params.password}" />
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_test_mode">{__("test_mode")}</label>
        <div class="controls">
            <input type="hidden" name="shipping_data[service_params][test_mode]" value="N" />
            <input id="elm_shipping_can_test_mode" type="checkbox" name="shipping_data[service_params][test_mode]" value="Y" {if $shipping.service_params.test_mode == "Y"}checked="checked"{/if} />
        </div>
    </div>
</fieldset>

{include file="common/subheader.tpl" title="{__("shippings.can.business_account_information")}"}
<fieldset>
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_customer_number">{__("shippings.can.customer_number")}</label>
        <div class="controls">
            <input id="elm_shipping_can_customer_number" type="text" name="shipping_data[service_params][customer_number]" size="30" value="{$shipping.service_params.customer_number}" />
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_contract_id">{__("shippings.can.contract_id")}</label>
        <div class="controls">
            <input id="elm_shipping_can_contract_id" type="text" name="shipping_data[service_params][contract_id]" size="30" value="{$shipping.service_params.contract_id}" />
        </div>
    </div>
</fieldset>

{include file="common/subheader.tpl" title="{__("shippings.can.options")}"}
<fieldset>
    {* SO - Signature *}
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_option_so">{__("shippings.can.option_so")}</label>
        <div class="controls">
            <input id="elm_shipping_can_option_so" type="checkbox" name="shipping_data[service_params][options][so]" value="so" {if $shipping.service_params.options.so}checked="checked"{/if} />
        </div>
    </div>
    {* COV - Coverage  (requires amount) *}
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_option_cov">{__("shippings.can.option_cov")}</label>
        <div class="controls">
            <input id="elm_shipping_can_option_cov" type="checkbox" name="shipping_data[service_params][options][cov]" value="cov" {if $shipping.service_params.options.cov}checked="checked"{/if} />
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_option_cov_amount">{__("shippings.can.option_cov_amount")}</label>
        <div class="controls">
            <input id="elm_shipping_can_option_cov_amount" type="text" name="shipping_data[service_params][options][cov_amount]" value="{$shipping.service_params.options.cov_amount}" />
        </div>
    </div>
    {* COD - Collect on delivery *}
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_option_cod">{__("shippings.can.option_cod")}</label>
        <div class="controls">
            <input id="elm_shipping_can_option_cod" type="checkbox" name="shipping_data[service_params][options][cod]" value="cod" {if $shipping.service_params.options.cod}checked="checked"{/if} />
        </div>
    </div>
    {* PA18 - Proof of Age Required - 18 *}
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_option_pa18">{__("shippings.can.option_pa18")}</label>
        <div class="controls">
            <input id="elm_shipping_can_option_pa18" type="checkbox" name="shipping_data[service_params][options][pa18]" value="pa18" {if $shipping.service_params.options.pa18}checked="checked"{/if} />
        </div>
    </div>
    {* PA19 - Proof of Age Required - 19 *}
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_option_pa19">{__("shippings.can.option_pa19")}</label>
        <div class="controls">
            <input id="elm_shipping_can_option_pa19" type="checkbox" name="shipping_data[service_params][options][pa19]" value="pa19" {if $shipping.service_params.options.pa19}checked="checked"{/if} />
        </div>
    </div>
    {* HFP - Card for pickup *}
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_option_hfp">{__("shippings.can.option_hfp")}</label>
        <div class="controls">
            <input id="elm_shipping_can_option_hfp" type="checkbox" name="shipping_data[service_params][options][hfp]" value="hfp" {if $shipping.service_params.options.hfp}checked="checked"{/if} />
        </div>
    </div>
    {* DNS - Do not safe drop *}
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_option_dns">{__("shippings.can.option_dns")}</label>
        <div class="controls">
            <input id="elm_shipping_can_option_dns" type="checkbox" name="shipping_data[service_params][options][dns]" value="dns" {if $shipping.service_params.options.dns}checked="checked"{/if} />
        </div>
    </div>
    {* LAD - Leave at door - do not card *}
    <div class="control-group">
        <label class="control-label" for="elm_shipping_can_option_lad">{__("shippings.can.option_lad")}</label>
        <div class="controls">
            <input id="elm_shipping_can_option_lad" type="checkbox" name="shipping_data[service_params][options][lad]" value="lad" {if $shipping.service_params.options.lad}checked="checked"{/if} />
        </div>
    </div>
</fieldset>