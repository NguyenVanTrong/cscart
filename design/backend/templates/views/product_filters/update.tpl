{capture name="mainbox"}

{script src="js/tygh/tabs.js"}

{if $filter}
    {assign var="id" value=$filter.filter_id}
{else}
    {assign var="id" value=0}
{/if}

{assign var="allow_save" value=true}
{if "ULTIMATE"|fn_allowed_for}
    {assign var="allow_save" value=$filter|fn_allow_save_object:"product_filters"}
{/if}

<div id="content_group{$id}">

<form action="{""|fn_url}" name="update_filter_form_{$id}" enctype="multipart/form-data" method="post" class="form-horizontal form-edit {if ""|fn_check_form_permissions || !$allow_save} cm-hide-inputs{/if}">

<input type="hidden" class="cm-no-hide-input" name="filter_id" value="{$id}" />
{if $in_popup}
    {$redirect_url = "product_filters.manage"|fn_url}
{else}
    {$redirect_url = $config.current_url}
{/if}
<input type="hidden" class="cm-no-hide-input" name="redirect_url" value="{$smarty.request.return_url|default:$redirect_url}" />

<div class="tabs cm-j-tabs">
    <ul class="nav nav-tabs">
        <li id="tab_details_{$id}" class="cm-js active"><a>{__("general")}</a></li>
        <li id="tab_categories_{$id}" class="cm-js"><a>{__("categories")}</a></li>
    </ul>
</div>
<div class="cm-tabs-content" id="tabs_content_{$id}">
    <div id="content_tab_details_{$id}">
    <fieldset>
        <div class="control-group">
            <label for="elm_filter_name_{$id}" class="control-label cm-required">{__("name")}</label>
            <div class="controls">
                <input type="text" id="elm_filter_name_{$id}" name="filter_data[filter]" class="span9" value="{$filter.filter}" />
            </div>
        </div>

        {if "ULTIMATE"|fn_allowed_for}
            {include file="views/companies/components/company_field.tpl"
                name="filter_data[company_id]"
                id="elm_filter_data_`$id`"
                selected=$filter.company_id
            }
        {/if}

        <div class="control-group">
            <label class="control-label" for="elm_filter_position_{$id}">{__("position_short")}</label>
            <div class="controls">
            <input type="text" id="elm_filter_position_{$id}" name="filter_data[position]" size="3" value="{$filter.position}{if !$id}0{/if}"/>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label cm-required" for="elm_filter_filter_by_{$id}">{__("filter_by")}</label>
            <div class="controls">
            {if !$id}
                {* F - feature, R - range field, B - base field *}
                <select name="filter_data[filter_type]" onchange="fn_check_product_filter_type(this.value, 'tab_variants_{$id}', {$id});" id="elm_filter_filter_by_{$id}" >
                {if $filter_features}
                    <optgroup label="{__("features")}">
                    {foreach from=$filter_features item=feature}
                        <option value="{if $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum || $feature.feature_type == "ProductFeatures::NUMBER_SELECTBOX"|enum}R{elseif $feature.feature_type == "ProductFeatures::DATE"|enum}D{else}F{/if}F-{$feature.feature_id}">{$feature.description}</option>
                    {if $feature.subfeatures}
                    {foreach from=$feature.subfeatures item=subfeature}
                        <option value="{if $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum || $feature.feature_type == "ProductFeatures::NUMBER_SELECTBOX"|enum}R{elseif $feature.feature_type == "ProductFeatures::DATE"|enum}D{else}F{/if}F-{$subfeature.feature_id}">{$subfeature.description}</option>
                    {/foreach}
                    {/if}
                    {/foreach}
                    </optgroup>
                {/if}
                {if $filter_fields}
                    <optgroup label="{__("product_fields")}">
                    {foreach from=$filter_fields item="field" key="field_type"}
                        {if !$field.hidden}
                            <option value="{if $field.is_range}R{else}B{/if}-{$field_type}">{__($field.description)}</option>
                        {/if}
                    {/foreach}
                    </optgroup>
                {/if}
                </select>
            {else}
                <input type="hidden" name="filter_data[filter_type]" value="{if $filter.feature_id}FF-{$filter.feature_id}{else}{if $filter_fields[$filter.field_type].is_range}R{else}B{/if}-{$filter.field_type}{/if}">
                <span class="shift-input">{$filter.feature}{if $filter.feature_group} ({$filter.feature_group}){/if}</span>
            {/if}
            </div>
        </div>

        <div class="control-group{if !$filter.slider} hidden{/if}" id="round_to_{$id}_container">
            <label class="control-label" for="elm_filter_round_to_{$id}">{__("round_to")}</label>
            <div class="controls">
            <select name="filter_data[round_to]" id="elm_filter_round_to_{$id}">
                <option value="0.01"{if $filter.round_to == "0.01"} selected="selected"{/if}>0.01</option>
                <option value="0.1"{if $filter.round_to == "0.1"} selected="selected"{/if}>0.1</option>
                <option value="1"  {if $filter.round_to == "1"}   selected="selected"{/if}>1</option>
                <option value="10" {if $filter.round_to == "10"}  selected="selected"{/if}>10</option>
                <option value="100"{if $filter.round_to == "100"} selected="selected"{/if}>100</option>
            </select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_filter_display_{$id}">{__("display_type")}</label>
            <div class="controls">
            <select name="filter_data[display]" id="elm_filter_display_{$id}">
                <option value="Y" {if $filter.display == 'Y'} selected="selected"{/if}>{__("expanded")}</option>
                <option value="N" {if $filter.display == 'N'} selected="selected"{/if}>{__("minimized")}</option>
            </select>
            </div>
        </div>

        <div class="control-group {if !($filter.feature_id || $filter_fields[$filter.field_type].is_range || $filter.feature == 'Vendor')} hidden{/if}" id="display_count_{$id}_container">
            <label class="control-label" for="elm_filter_display_count_{$id}">{__("display_variants_count")}</label>
            <div class="controls">
            <input type="text" id="elm_filter_display_count_{$id}" name="filter_data[display_count]" value="{$filter.display_count|default:"10"}" />
            </div>
        </div>
    </fieldset>
    </div>

    <div class="hidden" id="content_tab_categories_{$id}">
        {include file="pickers/categories/picker.tpl" company_ids=$picker_selected_companies multiple=true input_name="filter_data[categories_path]" item_ids=$filter.categories_path data_id="category_ids_`$id`" no_item_text=__("text_all_categories_included") use_keys="N" owner_company_id=$filter.company_id but_meta="pull-right"}
    </div>
</div>

{if $in_popup}
    <div class="buttons-container">
        {if "ULTIMATE"|fn_allowed_for && !$allow_save}
            {assign var="hide_first_button" value=true}
        {/if}
        {include file="buttons/save_cancel.tpl" but_name="dispatch[product_filters.update]" cancel_action="close" hide_first_button=$hide_first_button save=$id }
    </div>
{else}
    {capture name="buttons"}
        {if $filter|fn_allow_save_object:"product_filters"}
            {if "ULTIMATE"|fn_allowed_for && !$allow_save}
                {assign var="hide_first_button" value=true}
            {/if}
            {include file="buttons/save_cancel.tpl" but_role="submit-link" but_name="dispatch[product_filters.update]" but_target_form="update_filter_form_{$id}" save=$id}
        {/if}
    {/capture}
{/if}

</form>
<!--content_group{$id}--></div>

{if !$id}
<script type="text/javascript">
    fn_check_product_filter_type(Tygh.$('#elm_filter_filter_by_{$id}').val(), 'tab_variants_{$id}', '{$id}');
</script>
{/if}
{/capture}

{if $in_popup}
    {$smarty.capture.mainbox nofilter}
{else}
    {include file="common/mainbox.tpl" title="{__("filter")}: `$filter.filter`" content=$smarty.capture.mainbox buttons=$smarty.capture.buttons select_languages=true}
{/if}
