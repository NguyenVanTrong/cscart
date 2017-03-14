{if $feature.prefix}<span>{$feature.prefix}</span>{/if}
{if $feature.feature_type == "ProductFeatures::TEXT_SELECTBOX"|enum
    || $feature.feature_type == "ProductFeatures::NUMBER_SELECTBOX"|enum
    || $feature.feature_type == "ProductFeatures::EXTENDED"|enum}
    {assign var="suffix" value=$data_name|md5}

    {if $over}
        {assign var="input_id" value="field_`$field`__`$feature.feature_id`_"}
    {else}
        {assign var="input_id" value="feature_`$feature.feature_id`_`$suffix`"}
    {/if}
    <input type="hidden" name="{$data_name}[product_features][{$feature.feature_id}]" id="{$input_id}"
           value="{$selected|default:$feature.variant_id}"{if $over} disabled="disabled"{/if}/>
    <select {if $over}id="field_{$field}__{$feature.feature_id}_" disabled="disabled"{/if}
            class="cm-object-selector{if $over} elm-disabled{/if}"
            name="{$data_name}[product_features][{$feature.feature_id}]"
            data-ca-enable-images="true"
            data-ca-image-width="30"
            data-ca-image-height="30"
            data-ca-enable-search="true"
            data-ca-load-via-ajax="{$feature.use_variant_picker|default:false}"
            data-ca-page-size="10"
            data-ca-data-url="{"product_features.get_variants_list?feature_id=`$feature.feature_id`&lang_code=`$descr_sl`"|fn_url nofilter}"
            data-ca-placeholder="-{__("none")}-"
            data-ca-allow-clear="true">
        <option value="">-{__("none")}-</option>
        {foreach from=$feature.variants item="variant"}
            <option value="{$variant.variant_id}"{if $variant.selected} selected="selected"{/if}>{$variant.variant}</option>
        {/foreach}
        <option value="">-{__("none")}-</option>
    </select>
{elseif $feature.feature_type == "ProductFeatures::MULTIPLE_CHECKBOX"|enum}
    <input type="hidden" name="{$data_name}[product_features][{$feature.feature_id}]" value=""
           {if $over}id="field_{$field}__{$feature.feature_id}_" disabled="disabled"{/if} />
    <select {if $over}id="field_{$field}__{$feature.feature_id}_" disabled="disabled"{/if}
            class="cm-object-selector{if $over} elm-disabled{/if}"
            name="{$data_name}[product_features][{$feature.feature_id}][]"
            multiple
            data-ca-enable-images="true"
            data-ca-image-width="30"
            data-ca-image-height="30"
            data-ca-enable-search="true"
            data-ca-close-on-select="false"
            data-ca-load-via-ajax="{$feature.use_variant_picker|default:false}"
            data-ca-page-size="10"
            data-ca-data-url="{"product_features.get_variants_list?feature_id=`$feature.feature_id`&lang_code=`$descr_sl`"|fn_url nofilter}">
        {foreach from=$feature.variants item="variant"}
            <option value="{$variant.variant_id}"{if $variant.selected} selected="selected"{/if}>{$variant.variant}</option>
        {/foreach}
    </select>
{elseif $feature.feature_type == "ProductFeatures::SINGLE_CHECKBOX"|enum}
    <input type="hidden" name="{$data_name}[product_features][{$feature.feature_id}]" value="N" {if $over}disabled="disabled" id="field_{$field}__{$feature.feature_id}_copy"{/if} />
    <input type="checkbox" name="{$data_name}[product_features][{$feature.feature_id}]" value="Y" {if $over}id="field_{$field}__{$feature.feature_id}_" disabled="disabled" class="elm-disabled"{/if} {if $feature.value == "Y"}checked="checked"{/if} />
{elseif $feature.feature_type == "ProductFeatures::DATE"|enum}
    {if $over}
        {assign var="date_id" value="field_`$field`__`$feature.feature_id`_"}
        {assign var="date_extra" value=" disabled=\"disabled\""}
        {assign var="d_meta" value="input-text-disabled"}
    {else}
        {assign var="date_id" value="date_`$pid``$feature.feature_id`"}
        {assign var="date_extra" value=""}
        {assign var="d_meta" value=""}
    {/if}
    {$feature.value}{include file="common/calendar.tpl" date_id=$date_id date_name="`$data_name`[product_features][`$feature.feature_id`]" date_val=$feature.value_int start_year=$settings.Company.company_start_year extra=$date_extra date_meta=$d_meta}
{else}
    <input type="text" name="{$data_name}[product_features][{$feature.feature_id}]" value="{if $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum}{if $feature.value_int != ""}{$feature.value_int|floatval}{/if}{else}{$feature.value}{/if}" {if $over} id="field_{$field}__{$feature.feature_id}_" disabled="disabled"{/if} class="input-text {if $over}input-text-disabled{/if} {if $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum}cm-value-decimal{/if}" />
{/if}
{if $feature.suffix}<span>{$feature.suffix}</span>{/if}
<input type="hidden" name="{$data_name}[active_features][]" value="{$feature.feature_id}" />