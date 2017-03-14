{assign var="paypal_currencies" value="payflow"|fn_paypal_get_currencies}

{include file="addons/paypal/common/connect_to_paypal.tpl"}

<hr>

<div class="control-group">
    <label class="control-label" for="pf_currency">{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="pf_currency">
            {foreach from=$paypal_currencies item="currency"}
                <option value="{$currency.id}"{if !$currency.active} disabled="disabled"{/if}{if $processor_params.currency == $currency.id} selected="selected"{/if}>{$currency.name}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="order_prefix">{__("order_prefix")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][order_prefix]" id="order_prefix" size="60" value="{$processor_params.order_prefix}" >
    </div>
</div>

{include file="common/subheader.tpl" title=__("addons.paypal.technical_details") target="#section_technical_details"}

<div id="section_technical_details">

    <div class="control-group">
        <label class="control-label" for="username">{__("username")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][username]" id="username" size="60" value="{$processor_params.username}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="password">{__("password")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][password]" id="password" size="60" value="{$processor_params.password}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="vendor">{__("paypal_vendor")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][vendor]" id="vendor" size="60" value="{$processor_params.vendor}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="partner">{__("partner")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][partner]" id="partner" size="60" value="{$processor_params.partner}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="mode">{__("test_live_mode")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][mode]" id="mode">
                <option value="test"{if $processor_params.mode eq "test"} selected="selected"{/if}>{__("test")}</option>
                <option value="live"{if $processor_params.mode eq "live"} selected="selected"{/if}>{__("live")}</option>
            </select>
        </div>
    </div>
</div>