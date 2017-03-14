{$id = $column->getId()}

{capture name="tabsbox"}
<div id="content_snippet_product_table">
    <form action="{""|fn_url}" method="post" name="table_column_form_{$id}" class="cm-ajax form-horizontal">

        <input type="hidden" name="result_ids" value="content_table_column_list_{$snippet_id}" />
        <input type="hidden" name="return_url" value="{$return_url}" />
        <input type="hidden" name="column_id" value="{$id}" />
        <input type="hidden" name="snippet_id" value="{$snippet_id}" />

        <div id="content_tab_table_column_{$id}">
            <fieldset>

                <div class="control-group">
                    <label for="elm_template_subject_{$id}" class="cm-required cm-focus control-label">{__("name")}:</label>
                    <div class="controls">
                        <input id="elm_template_subject_{$id}" type="text" name="column[name]" value="{$column->getName()}" class="span9 cm-emltpl-set-active">
                    </div>
                </div>

                <div class="control-group">
                    <label class="cm-required control-label" for="elm_table_column_value_{$id}">{__("template")}:</label>
                    <div class="controls">
                        <textarea id="elm_table_column_value_{$id}" name="column[template]" cols="55" rows="4" class="span9 cm-wysiwyg">{$column->getTemplate()}</textarea>
                    </div>
                </div>

                {include file="common/select_status.tpl" input_name="column[status]" id="elm_column_status_{$id}" obj=$column->toArray() hidden=false}

                <div class="control-group">
                    <label class="control-label">{__("variables")}:</label>
                    <div class="controls">
                        {foreach from=$variables item="variable"}
                            {$name = $variable->getName()}
                            {$attributes = $variable->getAttributes()}

                            {if $variable->getAlias()}
                                {$name = $variable->getAlias()}
                            {/if}

                            {if $attributes}
                                {include file="views/snippets/components/variable_attributes.tpl" attributes=$attributes name=$name}
                            {else}
                                <span class="label hand">{ldelim}{ldelim} {$name} {rdelim}{rdelim}</span>
                            {/if}

                        {/foreach}
                    </div>
                </div>

            </fieldset>
        <!--content_tab_table_column_{$id}--></div>

        <div class="buttons-container" id="content_tab_table_column_buttons_{$id}">
            {if $id && $column->isModified()}
                {assign var="r_url" value=$config.current_url|escape:url}
                {capture name="tools_list"}
                    <li>{btn type="text" href="snippets.restore_table_column?column_id=$id&return_url=$r_url" class="cm-confirm cm-ajax" data=["data-ca-confirm-text" => "{__("text_restore_question")}", "data-ca-event" => "ce.formajaxpost_table_column_form_{$id}"] text=__("restore") method="POST"}</li>
                {/capture}
                {dropdown content=$smarty.capture.tools_list class="cm-tab-tools droptop" id="tools_general"}
            {/if}

            {include file="buttons/save_cancel.tpl" but_name="dispatch[snippets.update_table_column]" cancel_action="close" save=$id}
        <!--content_tab_table_column_buttons_{$id}--></div>

    </form>
<!--content_snippet_product_table--></div>
    <script type="text/javascript">
        (function(_, $) {
            $.ceEvent('on', 'ce.formajaxpost_table_column_form_{$id}', function(response_data, params) {
                if (response_data.failed_request) {
                    return false;
                }

                var $dialog = $.ceDialog('get_last');

                $dialog.find('.cm-wysiwyg').ceEditor('destroy');
                $dialog.ceDialog('destroy');
                $dialog.remove();
            });
        }(Tygh, Tygh.$));
    </script>
{/capture}

{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox track=true}