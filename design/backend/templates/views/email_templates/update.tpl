{script src="js/tygh/email_templates.js"}

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{assign var="return_url" value=$config.current_url}
{assign var="return_url_escape" value=$return_url|escape:"url"}

{capture name="mainbox"}

{$id = $email_template->getId()}
{$params = $email_template->getParams()}

{capture name="tabsbox"}

<div id="content_general">
    <form action="{""|fn_url}" method="post" enctype="multipart/form-data" name="email_template_form" class="form-horizontal">

    <input type="hidden" name="selected_section" id="selected_section" value="{$smarty.request.selected_section}" />
    <input type="hidden" name="result_ids" value="preview_dialog" />
    <input type="hidden" name="template_id" value="{$id}" />

    <fieldset>
        <div class="control-group">
            <label for="elm_email_template_subject_{$id}" class="cm-required control-label">{__("subject")}:</label>
            <div class="controls">
                <input id="elm_email_template_subject_{$id}" type="text" name="email_template[subject]" value="{$email_template->getSubject()}" class="span9 cm-emltpl-set-active cm-focus">
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_email_template_template_{$id}">{__("template")}:</label>
            <div class="controls">
                <textarea id="elm_email_template_template_{$id}" name="email_template[template]" cols="55" rows="14" class="span9 cm-emltpl-set-active">{$email_template->getTemplate()}</textarea>
            </div>
        </div>

        {include file="common/select_status.tpl" input_name="email_template[status]" id="elm_email_template_status_{$id}" obj=$email_template->toArray() hidden=false}

        {if $params_schema}
            {foreach from=$params_schema key=name item=field}
                <div class="control-group">
                    <label class="control-label" for="elm_email_template_params_{$id}_{$name}">{__($field.title)}{if $field.description}{include file="common/tooltip.tpl" tooltip=__($field.description)}{/if}:</label>
                    <div class="controls">

                        {if $field.type == 'checkbox'}
                            <input type="hidden" name="email_template[params][{$name}]" value="N">
                            <input type="checkbox" id="elm_email_template_params_{$id}_{$name}" name="email_template[params][{$name}]" value="Y"{if $params.$name == "Y"} checked="checked"{/if} />
                        {elseif $field.type == 'selectbox'}
                            <select name="email_template[params][{$name}]" id="elm_email_template_params_{$id}_{$name}">
                                <option value=""> - </option>
                                {foreach from=$field.variants key="variant_key" item="variant_name"}
                                    <option value="{$variant_key}" {if $variant_key == $params.$name} selected="selected"{/if}>{$variant_name}</option>
                                {/foreach}
                            </select>
                        {elseif $field.type == 'checkboxes'}
                            <input type="hidden" name="email_template[params][{$name}]">
                            {foreach from=$field.variants key="variant_key" item="variant_name"}
                                <label class="checkbox inline" for="elm_email_template_params_{$id}_{$name}_{$variant_key}">
                                    <input type="checkbox" id="elm_email_template_params_{$id}_{$name}_{$variant_key}" name="email_template[params][{$name}][]" value="{$variant_key}"{if $variant_key|in_array:$params.$name} checked="checked"{/if} />
                                    {$variant_name}
                                </label>
                            {/foreach}
                        {elseif $field.type == 'textarea'}
                            <textarea id="elm_email_template_params_{$id}_{$name}" name="email_template[params][{$name}]" cols="55" rows="3" class="span9">{$params.$name}</textarea>
                        {else}
                            <input type="text" id="elm_email_template_params_{$id}_{$name}" name="email_template[params][{$name}]" value="{$params.$name}" />
                        {/if}
                    </div>
                </div>
            {/foreach}
        {/if}

    </fieldset>
    
    </form>

</div>

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox track=true}

{include file="views/email_templates/preview.tpl" preview=[]}

{/capture}

{capture name="sidebar"}
    <div class="sidebar-row">
        <h6>{__("variables")}</h6>
        <ul class="nav nav-list">
            {foreach from=$variables item="variable"}
                <li><span class="cm-emltpl-insert-variable label hand" data-ca-template-value="{$variable}">{$variable}</span></li>
            {/foreach}
        </ul>
    </div>
    
    <div class="sidebar-row" id="sidebar_snippets">
        <h6>{__("snippets")}</h6>
        <ul class="nav nav-list">
            {foreach from=$snippets item="snippet"}
                {if $snippet->getStatus() == "A"}
                    <li><span class="cm-emltpl-insert-variable label label-info hand" data-ca-template-value="{$snippet->getCallTag()}">{$snippet->getCode()}</span></li>
                {/if}
            {/foreach}
        </ul>
    <!--sidebar_snippets--></div>
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        {hook name="email_templates:update_tools_list_general"}
            <li>{btn type="list" text=__("send_test_email") class="cm-ajax" dispatch="dispatch[email_templates.send]" form="email_template_form"}</li>
            <li>{btn type="list" text=__("preview") class="cm-ajax cm-form-dialog-opener" dispatch="dispatch[email_templates.preview]" form="email_template_form"}</li>

            {if $email_template->isModified()}
                {assign var="r_url" value=$config.current_url|escape:url}
                <li>{btn type="text" href="email_templates.restore?template_id=$id&return_url=$r_url" class="cm-confirm" data=["data-ca-confirm-text" => "{__("text_restore_question")}"] text=__("restore") method="POST"}</li>
            {/if}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list class="cm-tab-tools" id="tools_general"}

    {include file="buttons/save_cancel.tpl" but_role="submit-link" but_name="dispatch[email_templates.update]" but_target_form="email_template_form" save=$id}
{/capture}

{include file="common/mainbox.tpl"
    title="{__("editing_email_template")}: {$email_template->getName()}"
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    sidebar=$smarty.capture.sidebar
    sidebar_position="left"
}
