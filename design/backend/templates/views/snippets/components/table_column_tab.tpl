{assign var="can_update" value=fn_check_permissions('snippets', 'update', 'admin', 'POST')}
{assign var="edit_link_text" value=__("edit")}

{if !$can_update}
    {assign var="edit_link_text" value=__("view")}
{/if}

<form action="{""|fn_url}" method="post" name="table_columns_form_{$snippet->getId()}" class="form-horizontal">

    <input type="hidden" name="return_url" value="{$return_url}" />
    <input type="hidden" name="result_ids" value="content_table_columns_list_{$snippet->getId()}" />

    <div class="items-container {if $can_update}cm-sortable{/if}" {if $can_update}data-ca-sortable-table="template_table_columns" data-ca-sortable-id-name="column_id"{/if} id="content_table_column_list_{$snippet->getId()}">
        {if $columns}
            <table class="table table-middle table-objects table-striped">
                <tbody>
                {foreach from=$columns item="column"}
                    {include file="common/object_group.tpl"
                        id=$column->getId()
                        text=$column->getName()
                        status=$column->getStatus()
                        href="snippets.update_table_column?column_id={$column->getId()}&return_url={$return_url_escape}"
                        object_id_name="column_id"
                        table="template_table_columns"
                        href_delete="snippets.delete_table_column?column_id={$column->getId()}&return_url={$return_url_escape}"
                        delete_target_id="content_table_column_list_{$snippet->getId()}"
                        header_text="{__("editing_table_column")}: {$column->getName()}"
                        additional_class="cm-sortable-row cm-sortable-id-{$column->getId()}"
                        no_table=true
                        draggable=true
                        link_text=$edit_link_text
                        nostatus=!$can_update
                    }
                {/foreach}
                </tbody>
            </table>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}
    <!--content_table_column_list_{$snippet->getId()}--></div>
</form>