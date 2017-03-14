<div class="control-group">
    <label class="control-label" for="rg_login">{__("login")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][login]" id="rg_login" value="{$processor_params.login}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="rg_transaction_key">{__("transaction_key")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][transaction_key]" id="rg_transaction_key" value="{$processor_params.transaction_key}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="rg_transaction_type">{__("transaction_type")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][transaction_type]" id="rg_transaction_type">
            <option value="P" {if $processor_params.transaction_type == "P"}selected="selected"{/if}>{__("authorize_capture")}</option>
            <option value="A" {if $processor_params.transaction_type == "A"}selected="selected"{/if}>{__("authorize_only")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="rg_currency">{__("currency")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][currency]" id="rg_currency">
            <option value="USD"{if $processor_params.currency == "USD"} selected="selected"{/if}>{__("currency_code_usd")}</option>
            <option value="EUR"{if $processor_params.currency == "EUR"} selected="selected"{/if}>{__("currency_code_eur")}</option>
            <option value="AUD"{if $processor_params.currency == "AUD"} selected="selected"{/if}>{__("currency_code_aud")}</option>
            <option value="CAD"{if $processor_params.currency == "CAD"} selected="selected"{/if}>{__("currency_code_cad")}</option>
            <option value="CHF"{if $processor_params.currency == "CHF"} selected="selected"{/if}>{__("currency_code_chf")}</option>
            <option value="CZK"{if $processor_params.currency == "CZK"} selected="selected"{/if}>{__("currency_code_czk")}</option>
            <option value="DKK"{if $processor_params.currency == "DKK"} selected="selected"{/if}>{__("currency_code_dkk")}</option>
            <option value="FRF"{if $processor_params.currency == "FRF"} selected="selected"{/if}>{__("currency_code_frf")}</option>
            <option value="GBP"{if $processor_params.currency == "GBP"} selected="selected"{/if}>{__("currency_code_gbp")}</option>
            <option value="HKD"{if $processor_params.currency == "HKD"} selected="selected"{/if}>{__("currency_code_hkd")}</option>
            <option value="HUF"{if $processor_params.currency == "HUF"} selected="selected"{/if}>{__("currency_code_huf")}</option>
            <option value="ILS"{if $processor_params.currency == "ILS"} selected="selected"{/if}>{__("currency_code_ils")}</option>
            <option value="JPY"{if $processor_params.currency == "JPY"} selected="selected"{/if}>{__("currency_code_jpy")}</option>
            <option value="LTL"{if $processor_params.currency == "LTL"} selected="selected"{/if}>{__("currency_code_ltl")}</option>
            <option value="LVL"{if $processor_params.currency == "LVL"} selected="selected"{/if}>{__("currency_code_lvl")}</option>
            <option value="MXN"{if $processor_params.currency == "MXN"} selected="selected"{/if}>{__("currency_code_mxn")}</option>
            <option value="NOK"{if $processor_params.currency == "NOK"} selected="selected"{/if}>{__("currency_code_nok")}</option>
            <option value="NZD"{if $processor_params.currency == "NZD"} selected="selected"{/if}>{__("currency_code_nzd")}</option>
            <option value="PLN"{if $processor_params.currency == "PLN"} selected="selected"{/if}>{__("currency_code_pln")}</option>
            <option value="RUR"{if $processor_params.currency == "RUR"} selected="selected"{/if}>{__("currency_code_rur")}</option>
            <option value="SEK"{if $processor_params.currency == "SEK"} selected="selected"{/if}>{__("currency_code_sek")}</option>
            <option value="SGD"{if $processor_params.currency == "SGD"} selected="selected"{/if}>{__("currency_code_sgd")}</option>
            <option value="SKK"{if $processor_params.currency == "SKK"} selected="selected"{/if}>{__("currency_code_skk")}</option>
            <option value="THB"{if $processor_params.currency == "THB"} selected="selected"{/if}>{__("currency_code_thb")}</option>
            <option value="TRY"{if $processor_params.currency == "TRY"} selected="selected"{/if}>{__("currency_code_try")}</option>
            <option value="KPW"{if $processor_params.currency == "KPW"} selected="selected"{/if}>{__("currency_code_kpw")}</option>
            <option value="KRW"{if $processor_params.currency == "KRW"} selected="selected"{/if}>{__("currency_code_krw")}</option>
            <option value="ZAR"{if $processor_params.currency == "ZAR"} selected="selected"{/if}>{__("currency_code_zar")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="rg_test_mode">{__("test_live_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][mode]" id="rg_test_mode">
            <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
            <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
        </select>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="rg_avs_mode">{__("payments.rocketgate.avs_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][avsmode]" id="rg_avs_mode">
            <option value="true" {if $processor_params.avsmode == "true"}selected="selected"{/if}>{__("true")}</option>
            <option value="false" {if $processor_params.avsmode == "false"}selected="selected"{/if}>{__("false")}</option>
			<option value="ignore" {if $processor_params.avsmode == "ignore"}selected="selected"{/if}>{__("payments.rocketgate.ignore")}</option>
        </select>
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="rg_scrub_mode">{__("payments.rocketgate.scrub_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][scrubmode]" id="rg_scrub_mode"payments.rocketgate.scrub_mode>
            <option value="true" {if $processor_params.scrubmode == "true"}selected="selected"{/if}>{__("true")}</option>
            <option value="false" {if $processor_params.scrubmode == "false"}selected="selected"{/if}>{__("false")}</option>
			<option value="ignore" {if $processor_params.scrubmode == "ignore"}selected="selected"{/if}>{__("payments.rocketgate.ignore")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="order_prefix">{__("order_prefix")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][order_prefix]" id="order_prefix" value="{$processor_params.order_prefix}" >
    </div>
</div>

<hr>

<p>
<b>Testing Info:</b>
This info will return the specified responses in demo mode and will have no effect on live mode where real data is required.
<br/>Refer to the Test_Values_and_Responses.pdf documentation for a complete list of scripted test values and responses.
<br/><br/><b>Test Credit Card Numbers:</b>
<br>Visa#: 4012 8888 8888 1881
<br>MC#: 5105 1051 0510 5100
<br>Discover#: 6011 1111 1111 1117
<br>AMEX#: 3782 822 4631 0005
<br><br> Any future date can be used for the expiration date.
<br/><br/><b>Test CVV Numbers:</b>
<br/>No Match: 0001
<br/>Not processed: 0002
<br/>Should have been present: 0003
<br/>Unable to process: 0004
<br/><br/>Any other 3 or 4 digit number can be used for the CVV Code.
<br/><br/><b>Test Zip Codes (AVS tests):</b>
<br/>Street match, Zip No Match: 00001<br/>No Match: 00008
<br>Exact Match: 00015
<br/><br/><a href="http://www.rocketgate.com/" target="_blank">RocketGate</a>
</p>
