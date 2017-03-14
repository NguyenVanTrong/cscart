{$f_package_types = [
    "FEDEX_10KG_BOX", "FEDEX_25KG_BOX", "FEDEX_BOX", "FEDEX_ENVELOPE",
    "FEDEX_EXTRA_LARGE_BOX", "FEDEX_LARGE_BOX", "FEDEX_MEDIUM_BOX", "FEDEX_PAK",
    "FEDEX_SMALL_BOX", "FEDEX_TUBE", "YOUR_PACKAGING"
]}

{$f_drop_off_types = [
    "BUSINESS_SERVICE_CENTER", "DROP_BOX", "REGULAR_PICKUP", "REQUEST_COURIER", "STATION"
]}

{$f_options = [
    "BROKER_SELECT_OPTION", "CALL_BEFORE_DELIVERY",
    "COD", "COD_AMOUNT", "COD_COLLECTION_TYPE",
    "CUSTOM_DELIVERY_WINDOW",
    "DANGEROUS_GOODS", "DANGEROUS_GOODS_OPTIONS", "DANGEROUS_GOODS_ACCESSIBILITY",
    "DO_NOT_BREAK_DOWN_PALLETS", "DO_NOT_STACK_PALLETS", "DRY_ICE",
    "EAST_COAST_SPECIAL", "ELECTRONIC_TRADE_DOCUMENTS", "EXTREME_LENGTH",
    "FOOD", "FEDEX_ONE_RATE", "FREIGHT_GUARANTEE", "FREIGHT_TO_COLLECT",
    "FUTURE_DAY_SHIPMENT", "HOLD_AT_LOCATION", "HOME_DELIVERY_PREMIUM", "INSIDE_DELIVERY",
    "INSIDE_PICKUP", "INTERNATIONAL_CONTROLLED_EXPORT_SERVICE",
    "INTERNATIONAL_TRAFFIC_IN_ARMS_REGULATIONS", "LIFTGATE_DELIVERY", "LIFTGATE_PICKUP",
    "LIMITED_ACCESS_DELIVERY", "LIMITED_ACCESS_PICKUP", "PHARMACY_DELIVERY",
    "POISON", "PROTECTION_FROM_FREEZING", "RETURNS_CLEARANCE",
    "SATURDAY_DELIVERY", "SATURDAY_PICKUP", "TOP_LOAD"
]}

{$f_cod_collection_types = [
    "ANY", "CASH", "GUARANTEED_FUNDS"
]}

{$f_dangerous_goods_options = [
    "BATTERY", "HAZARDOUS_MATERIALS", "ORM_D", "LIMITED_QUANTITIES_COMMODITIES", "REPORTABLE_QUANTITIES",
    "SMALL_QUANTITY_EXCEPTION"
]}

{$f_dangerous_goods_accessibilities = ["ACCESSIBLE", "INACCESSIBLE"]}

<fieldset>

<div class="control-group">
    <label class="control-label" for="user_key">{__("authentication_key")}</label>
    <div class="controls">
    <input id="user_key" type="text" name="shipping_data[service_params][user_key]" size="30" value="{$shipping.service_params.user_key}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="user_key_password">{__("authentication_password")}</label>
    <div class="controls">
    <input id="user_key_password" type="text" name="shipping_data[service_params][user_key_password]" size="30" value="{$shipping.service_params.user_key_password}" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="account_number">{__("account_number")}</label>
    <div class="controls">
    <input id="account_number" type="text" name="shipping_data[service_params][account_number]" size="30" value="{$shipping.service_params.account_number}" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_fedex_meter_number">{__("ship_fedex_meter_number")}</label>
    <div class="controls">
    <input id="ship_fedex_meter_number" type="text" name="shipping_data[service_params][meter_number]" size="30" value="{$shipping.service_params.meter_number}" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_fedex_freight_account_number">{__("shippings.fedex.freight_account_number")}</label>
    <div class="controls">
        <input id="ship_fedex_freight_account_number" type="text" name="shipping_data[service_params][freight_account_number]" size="30" value="{$shipping.service_params.freight_account_number}" />
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
    <label class="control-label" for="package_type">{__("package_type")}</label>
    <div class="controls">
    <select id="package_type" name="shipping_data[service_params][package_type]">
        {foreach $f_package_types as $f_package_type}
            <option value="{$f_package_type}"{if $shipping.service_params.package_type == $f_package_type} selected="selected"{/if}>{__("ship_fedex_package_type_{$f_package_type|lower}")}</option>
        {/foreach}
    </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_fedex_drop_off_type">{__("ship_fedex_drop_off_type")}</label>
    <div class="controls">
    <select id="ship_fedex_drop_off_type" name="shipping_data[service_params][drop_off_type]">
        {foreach $f_drop_off_types as $f_drop_off_type}
            <option value="{$f_drop_off_type}"{if $shipping.service_params.drop_off_type == $f_drop_off_type} selected="selected"{/if}>{__("ship_fedex_drop_off_type_{$f_drop_off_type|lower}")}</option>
        {/foreach}
    </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="max_weight">{__("max_box_weight")}</label>
    <div class="controls">
    <input id="max_weight" type="text" name="shipping_data[service_params][max_weight_of_box]" size="30" value="{$shipping.service_params.max_weight_of_box|default:0}" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_fedex_height">{__("ship_fedex_height")}</label>
    <div class="controls">
    <input id="ship_fedex_height" type="text" name="shipping_data[service_params][height]" size="30" value="{$shipping.service_params.height}" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_fedex_width">{__("ship_fedex_width")}</label>
    <div class="controls">
    <input id="ship_fedex_width" type="text" name="shipping_data[service_params][width]" size="30" value="{$shipping.service_params.width}"/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_fedex_length">{__("ship_fedex_length")}</label>
    <div class="controls">
    <input id="ship_fedex_length" type="text" name="shipping_data[service_params][length]" size="30" value="{$shipping.service_params.length}"/>
    </div>
</div>

{include file="common/subheader.tpl" title=__("shippings.fedex.options")}
{foreach $f_options as $code}
    <div class="control-group">
        <label class="control-label" for="elm_shipping_fedex_option_{$code}">{__("shippings.fedex.option_{$code|lower}")}</label>
        <div class="controls">
            {if $code == "COD_AMOUNT"}
                <input id="elm_shipping_fedex_option_{$code}" type="text" name="shipping_data[service_params][options][{$code}]" value="{$shipping.service_params.options.$code}" />
            {elseif $code == "COD_COLLECTION_TYPE"}
                <select id="elm_shipping_fedex_option_{$code}" name="shipping_data[service_params][options][{$code}]">
                    {foreach $f_cod_collection_types as $f_cod_collection_type}
                        <option value="{$f_cod_collection_type}"{if $shipping.service_params.options.$code == $f_cod_collection_type} selected="selected"{/if}>{__("shippings.fedex.option_cod_collection_type_{$f_cod_collection_type|lower}")}</option>
                    {/foreach}
                </select>
            {elseif $code == "DANGEROUS_GOODS_OPTIONS"}
                <select id="elm_shipping_fedex_option_{$code}" name="shipping_data[service_params][options][{$code}][]" multiple="multiple">
                    {foreach $f_dangerous_goods_options as $f_dangerous_goods_option}
                        <option value="{$f_dangerous_goods_option}"{if $f_dangerous_goods_option|in_array:$shipping.service_params.options.$code} selected="selected"{/if}>{__("shippings.fedex.option_dangerous_goods_options_{$f_dangerous_goods_option|lower}")}</option>
                    {/foreach}
                </select>
            {elseif $code == "DANGEROUS_GOODS_ACCESSIBILITY"}
                <select id="elm_shipping_fedex_option_{$code}" name="shipping_data[service_params][options][{$code}]">
                    {foreach $f_dangerous_goods_accessibilities as $f_dangerous_goods_accessibility}
                        <option value="{$f_dangerous_goods_accessibility}"{if $shipping.service_params.options.$code == $f_dangerous_goods_accessibility} selected="selected"{/if}>{__("shippings.fedex.option_dangerous_goods_accessibility_{$f_dangerous_goods_accessibility|lower}")}</option>
                    {/foreach}
                </select>
            {else}
                <input id="elm_shipping_fedex_option_{$code}" type="checkbox" name="shipping_data[service_params][options][{$code}]" value="{$code}" {if $shipping.service_params.options.$code}checked="checked"{/if} />
            {/if}
        </div>
    </div>
{/foreach}

{if $code == "SMART_POST"}
{include file="common/subheader.tpl" title=__("ship_fedex_smart_post")}

<div class="control-group">
    <label class="control-label" for="package_type">{__("ship_fedex_indicia")}</label>
    <div class="controls">
    <select id="package_type" name="shipping_data[service_params][indicia]">
        <option value="PRESORTED_STANDARD" {if $shipping.service_params.indicia == "PRESORTED_STANDARD"}selected="selected"{/if}>{__("ship_fedex_indicia_presorted_standard")}</option>
        <option value="PARCEL_SELECT" {if $shipping.service_params.indicia == "PARCEL_SELECT"}selected="selected"{/if}>{__("ship_fedex_indicia_parcel_select")}</option>
        <option value="MEDIA_MAIL" {if $shipping.service_params.indicia == "MEDIA_MAIL"}selected="selected"{/if}>{__("ship_fedex_indicia_media_mail")}</option>
        <option value="PRESORTED_BOUND_PRINTED_MATTER" {if $shipping.service_params.indicia == "PRESORTED_BOUND_PRINTED_MATTER"}selected="selected"{/if}>{__("ship_fedex_indicia_presorted_bound_printed_matter")}</option>
    </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="package_type">{__("ship_fedex_ancillary_endorsement")}</label>
    <div class="controls">
    <select id="package_type" name="shipping_data[service_params][ancillary_endorsement]">
        <option value="" {if $shipping.service_params.ancillary_endorsement == ""}selected="selected"{/if}>{__("none")}</option>
        <option value="ADDRESS_CORRECTION" {if $shipping.service_params.ancillary_endorsement == "ADDRESS_CORRECTION"}selected="selected"{/if}>{__("ship_fedex_ancillary_endorsement_address_correction")}</option>
        <option value="CARRIER_LEAVE_IF_NO_RESPONSE" {if $shipping.service_params.ancillary_endorsement == "CARRIER_LEAVE_IF_NO_RESPONSE"}selected="selected"{/if}>{__("ship_fedex_ancillary_endorsement_carrier_leave_if_no_response")}</option>
        <option value="CHANGE_SERVICE" {if $shipping.service_params.ancillary_endorsement == "CHANGE_SERVICE"}selected="selected"{/if}>{__("ship_fedex_ancillary_endorsement_change_service")}</option>
        <option value="FORWARDING_SERVICE" {if $shipping.service_params.ancillary_endorsement == "FORWARDING_SERVICE"}selected="selected"{/if}>{__("ship_fedex_ancillary_endorsement_forwarding_service")}</option>
        <option value="RETURN_DELIVERY" {if $shipping.service_params.ancillary_endorsement == "RETURN_DELIVERY"}selected="selected"{/if}>{__("ship_fedex_ancillary_endorsement_return_delivery")}</option>
    </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="test_mode">{__("ship_fedex_special_services")}</label>
    <div class="controls">
    <input type="hidden" name="shipping_data[service_params][special_services]" value="N" />
    <input id="test_mode" type="checkbox" name="shipping_data[service_params][special_services]" value="Y" {if $shipping.service_params.special_services == "Y"}checked="checked"{/if}/>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_fedex_length">{__("ship_fedex_hub_id")}</label>
    <div class="controls">
    <input id="ship_fedex_length" type="text" name="shipping_data[service_params][hub_id]" size="30" value="{$shipping.service_params.hub_id}" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="ship_fedex_length">{__("ship_fedex_customer_manifest_id")}</label>
    <div class="controls">
    <input id="ship_fedex_length" type="text" name="shipping_data[service_params][customer_manifest_id]" size="30" value="{$shipping.service_params.customer_manifest_id}" />
    </div>
</div>
{/if}

</fieldset>