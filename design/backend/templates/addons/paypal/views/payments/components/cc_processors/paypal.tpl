{assign var="paypal_currencies" value="standard"|fn_paypal_get_currencies}

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
        <input type="text" name="payment_data[processor_params][order_prefix]" id="order_prefix" value="{$processor_params.order_prefix}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="elm_new_order_status">{__("addons.paypal.status_for_new_orders")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][new_order_status]" id="elm_new_order_status">
            <option value="" {if $processor_params.new_order_status == ""}selected="selected"{/if}>{__("open")}</option>
            <option value="{$smarty.const.STATUS_INCOMPLETED_ORDER}" {if $processor_params.new_order_status == $smarty.const.STATUS_INCOMPLETED_ORDER}selected="selected"{/if}>{__("incompleted")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label"></label>
    <div class="controls">
        <p id="txt_new_order_status_open"
           class="{if $processor_params.new_order_status != ""}hidden{/if}"
        >{__("addons.paypal.status_for_new_orders.open")}</p>
        <p id="txt_new_order_status_incomplete"
           class="{if $processor_params.new_order_status != $smarty.const.STATUS_INCOMPLETED_ORDER}hidden{/if}"
        >{__("addons.paypal.status_for_new_orders.incomplete")}</p>
    </div>
</div>

{include file="common/subheader.tpl" title=__("addons.paypal.technical_details") target="#section_technical_details"}

<div id="section_technical_details">

    <div class="control-group">
        <label class="control-label" for="account">{__("account")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][account]" id="account" value="{$processor_params.account}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="item_name">{__("paypal_item_name")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][item_name]" id="item_name" value="{$processor_params.item_name}" >
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="mode">{__("test_live_mode")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][mode]" id="mode">
                <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
                <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
            </select>
        </div>
    </div>
</div>

<script>
    (function(_, $) {
        $('#elm_new_order_status').change(function() {
            $('#txt_new_order_status_open,#txt_new_order_status_incomplete').toggleClass('hidden');
        });
    })(Tygh, Tygh.$);
</script>
