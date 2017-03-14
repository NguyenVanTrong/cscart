<p>{__("addons.divido.availability_notice")}</p>
<hr>

<div class="control-group">
    <label class="control-label" for="api_key">{__("api_key")}:</label>
    <div class="controls">
        <input type="text"
            name="payment_data[processor_params][api_key]"
            id="api_key"
            value="{$processor_params.api_key}"
            class="input-text"
            size="60"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="show_product_page_calculator">{__("addons.divido.show_product_page_calculator")}:</label>
    <div class="controls">
        <input type="checkbox"
            name="payment_data[processor_params][show_product_page_calculator]"
            id="show_product_page_calculator"
            value="Y"
            {if $processor_params.show_product_page_calculator == 'Y'} checked="checked"{/if}/>
    </div>
</div>

<div class="control-group hidden" id="control_product_price_limit">
    <label class="control-label" for="product_price_limit">{__("addons.divido.product_price_limit")}:</label>
    <div class="controls">
        <input type="text"
            name="payment_data[processor_params][product_price_limit]"
            id="product_price_limit"
            value="{$processor_params.product_price_limit}"
            class="input-text"
            size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="cart_amount_limit">{__("addons.divido.cart_amount_limit")}:</label>
    <div class="controls">
        <input type="text"
            name="payment_data[processor_params][cart_amount_limit]"
            id="cart_amount_limit"
            value="{$processor_params.cart_amount_limit}"
            class="input-text"
            size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="currency">{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="currency">
            <option value="GBP"{if $processor_params.currency == "GBP"} selected="selected"{/if}>{__("currency_code_gbp")}</option>
        </select>
    </div>
</div>

<script type="text/javascript" class="cm-ajax-force">
    (function(_, $) {
        $(document).ready(function() {

            var showPriceLimit = function () {
                if ($('#show_product_page_calculator:checked').length > 0) {
                    $('#control_product_price_limit').show('fast');
                } else {
                    $('#control_product_price_limit').hide('fast');
                }
            };

            showPriceLimit();

            $('#show_product_page_calculator').click(function () {
                showPriceLimit();
            });
        });
    }(Tygh, Tygh.$));
</script>