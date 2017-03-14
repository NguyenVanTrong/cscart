{if $page_type == $smarty.const.PAGE_TYPE_FORM}
<div id="content_build_form">

    <div class="control-group">
        <label for="form_subject" class="control-label">{__("addons.form_builder.email_subject_field")}:</label>
        <div class="controls">
            <select id="form_subject" name="page_data[form][general][{$smarty.const.FORM_SUBJECT}]">
                {$form_has_elements = false}
                {capture name="build_form_elements"}
                    <optgroup label="{__("addons.form_builder.form_fields")}">
                    {foreach from=$elements item="element"}
                        {if $element.element_type|in_array:[$smarty.const.FORM_INPUT, $smarty.const.FORM_SELECT]}
                            {$form_has_elements = true}
                            <option value="{$element.element_id}"{if $form[$smarty.const.FORM_SUBJECT] === $element.element_id} selected="selected"{/if}>{$element.description}</option>
                        {/if}
                    {/foreach}
                    </optgroup>
                {/capture}
                {if $form_has_elements}
                    {$smarty.capture.build_form_elements nofilter}
                {/if}
                <option value=""{if $form[$smarty.const.FORM_SUBJECT] == ""} selected="selected"{/if}>{__("addons.form_builder.form_name")}</option>
                <option value="0"{if $form[$smarty.const.FORM_SUBJECT] === "0"} selected="selected"{/if}>{__("addons.form_builder.other_subject")}</option>
            </select>
            <p class="{if $form[$smarty.const.FORM_SUBJECT] !== "0"}hidden{/if}" id="form_subject_text">
                <input type="text" name="page_data[form][general][{$smarty.const.FORM_SUBJECT_TEXT}]" value="{$form[$smarty.const.FORM_SUBJECT_TEXT]}" />
            </p>
        </div>
        <script>
            (function(_, $) {
                $('#form_subject').change(function() {
                    if ($(this).val() === "0") {
                        $('#form_subject_text').removeClass('hidden');
                    } else {
                        $('#form_subject_text').addClass('hidden');
                    }
                });
            })(Tygh, Tygh.$);
        </script>
    </div>
    
    <div class="control-group">
        <label for="form_submit_text" class="control-label">{__("form_submit_text")}:</label>
        {assign var="form_submit_const" value=$smarty.const.FORM_SUBMIT}
        <div class="controls">
            <textarea id="form_submit_text" class="cm-wysiwyg input-textarea-long" rows="5" cols="50" name="page_data[form][general][{$form_submit_const}]" rows="5">{$form.$form_submit_const}</textarea>
        </div>
        
    </div>

    <div class="control-group">
        <label for="form_recipient" class="cm-required control-label">{__("email_to")}:</label>
        {assign var="form_recipient_const" value=$smarty.const.FORM_RECIPIENT}
        <div class="controls">
            <input id="form_recipient" class="input-text" type="text" name="page_data[form][general][{$form_recipient_const}]" value="{$form.$form_recipient_const}">
        </div>
    </div>

    <div class="control-group">
        <label for="form_is_secure" class="control-label">{__("form_is_secure")}:</label>
        {assign var="form_secure_const" value=$smarty.const.FORM_IS_SECURE}
        <div class="controls">
                <input type="hidden" name="page_data[form][general][{$smarty.const.FORM_IS_SECURE}]" value="N">
                <span class="checkbox">
                    <input type="checkbox" id="form_is_secure" value="Y" {if $form.$form_secure_const == "Y"}checked="checked"{/if} name="page_data[form][general][{$form_secure_const}]">
                </span>
        </div>
    </div>

    {include file="addons/form_builder/views/pages/components/pages_form_elements.tpl"}

</div>
{/if}