<fieldset>

<div class="control-group">
    <label class="control-label" for="ship_ups_access_key">{__("ship_ups_access_key")}</label>
    <div class="controls">
    <input id="ship_ups_access_key" type="text" name="shipping_data[service_params][access_key]" size="30" value="{$shipping.service_params.access_key}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="username">{__("username")}</label>
    <div class="controls">
    <input id="username" type="text" name="shipping_data[service_params][username]" size="30" value="{$shipping.service_params.username}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="password">{__("password")}</label>
    <div class="controls">
    <input id="password" type="text" name="shipping_data[service_params][password]" size="30" value="{$shipping.service_params.password}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="sw_negotiated_rates">{__("use_negotiated_rates")}</label>
    <div class="controls">
    <input type="hidden" name="shipping_data[service_params][negotiated_rates]" value="N" />
    <input id="sw_negotiated_rates" type="checkbox" name="shipping_data[service_params][negotiated_rates]" value="Y" {if $shipping.service_params.negotiated_rates == "Y"}checked="checked"{/if} class="cm-combination" />
    </div>
</div>

<div id="negotiated_rates" class="{if $shipping.service_params.negotiated_rates != "Y"}hidden{/if}">
    <div class="control-group">
        <label class="control-label" for="shipper_number">{__("shipper_number")}</label>
        <div class="controls">
        <input id="shipper_number" type="text" name="shipping_data[service_params][shipper_number]" size="30" value="{$shipping.service_params.shipper_number}"/>
        </div>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="test_mode">{__("test_mode")}</label>
    <div class="controls">
    <input type="hidden" name="shipping_data[service_params][test_mode]" value="N" />
    <input id="test_mode" type="checkbox" name="shipping_data[service_params][test_mode]" value="Y" {if $shipping.service_params.test_mode == "Y"}checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_ups_pickup_type">{__("ship_ups_pickup_type")}</label>
    <div class="controls">
    <select id="ship_ups_pickup_type" name="shipping_data[service_params][pickup_type]">
        <option value="01" {if $shipping.service_params.pickup_type == "01"}selected="selected"{/if}>{__("ship_ups_pickup_type_01")}</option>
        <option value="03" {if $shipping.service_params.pickup_type == "03"}selected="selected"{/if}>{__("ship_ups_pickup_type_03")}</option>
        <option value="06" {if $shipping.service_params.pickup_type == "06"}selected="selected"{/if}>{__("ship_ups_pickup_type_06")}</option>
        <option value="19" {if $shipping.service_params.pickup_type == "19"}selected="selected"{/if}>{__("ship_ups_pickup_type_19")}</option>
        <option value="20" {if $shipping.service_params.pickup_type == "20"}selected="selected"{/if}>{__("ship_ups_pickup_type_20")}</option>
    </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="package_type">{__("package_type")}</label>
    <div class="controls">
    <select id="package_type" name="shipping_data[service_params][package_type]">
        {foreach ["01", "02", "03", "04", "21", "24", "25", "30", "2a", "2b", "2c"] as $package_type}
            <option value="{$package_type}"
                    {if $shipping.service_params.package_type == $package_type}selected="selected"{/if}
            >{__("shippings.ups.package_type.`$package_type`")}</option>
        {/foreach}
    </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="sw_delivery_confirmation">{__("ship_ups_use_delivery_confirmation")}</label>
    <div class="controls">
    <input type="hidden" name="shipping_data[service_params][delivery_confirmation]" value="N" />
    <input id="sw_delivery_confirmation" type="checkbox" name="shipping_data[service_params][delivery_confirmation]" value="Y" {if $shipping.service_params.delivery_confirmation == "Y"}checked="checked"{/if} class="cm-combination" />
    </div>
</div>

<div id="delivery_confirmation" class="control-group {if $shipping.service_params.delivery_confirmation != "Y"}hidden{/if}">
    <label class="control-label" for="dcist_type">{__("ship_ups_dcist_type")}</label>
    <div class="controls">
    <select id="dcist_type" name="shipping_data[service_params][dcist_type]">
        <option value="1" {if $shipping.service_params.dcist_type == "1"}selected="selected"{/if}>{__("ship_ups_delivery_confirmation")}</option>
        <option value="2" {if $shipping.service_params.dcist_type == "2"}selected="selected"{/if}>{__("ship_ups_delivery_confirmation_signature")}</option>
        <option value="3" {if $shipping.service_params.dcist_type == "3"}selected="selected"{/if}>{__("ship_ups_delivery_confirmation_adult_signature")}</option>
    </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="max_weight">{__("max_box_weight")}</label>
    <div class="controls">
    <input id="max_weight" type="text" name="shipping_data[service_params][max_weight_of_box]" size="30" value="{$shipping.service_params.max_weight_of_box|default:0}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="width">{__("width")}</label>
    <div class="controls">
    <input id="width" type="text" name="shipping_data[service_params][width]" size="30" value="{$shipping.service_params.width}"/></div>
</div>

<div class="control-group">
    <label class="control-label" for="height">{__("height")}</label>
    <div class="controls">
    <input id="height" type="text" name="shipping_data[service_params][height]" size="30" value="{$shipping.service_params.height}" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="length">{__("length")}</label>
    <div class="controls">
    <input id="length" type="text" name="shipping_data[service_params][length]" size="30" value="{$shipping.service_params.length}"/>
    </div>
</div>

</fieldset>