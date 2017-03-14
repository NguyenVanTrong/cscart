
{include file="common/subheader.tpl" title=__("addons.google_export.google_export") target="#acc_google_export"}
<div id="acc_google_export">
    <div class="control-group">
        <label class="control-label" for="google_name_option">{__("google_name_option")}</label>
        <div class="controls">
            <select id="google_name_option" name="option_data[google_export_name_option]">
                <option value="not_option" selected="selected">{__("none")}</option>
                {foreach from=$field_options item="field_option"}
                    <option value="{$field_option}" {if $option_data.google_export_name_option == $field_option}selected="selected"{/if}>{$field_option}</option>
                {/foreach}
            </select>
        </div>
    </div>
</div>
