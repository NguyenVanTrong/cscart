{capture name="mainbox"}

{assign var="r_url" value=$config.current_url|escape:url}
{assign var="can_update" value=fn_check_permissions('snippets', 'update', 'admin', 'POST')}
{assign var="edit_link_text" value=__("edit")}

{if !$can_update}
    {assign var="edit_link_text" value=__("view")}
{/if}

<form action="{""|fn_url}" method="post" name="manage_documents_form" id="manage_documents_form">
    <input type="hidden" name="return_url" value="{$config.current_url}">
    <div class="items-container" id="documents_list">
        {if $documents}
            <table width="100%" class="table table-middle table-objects">
                <thead>
                <tr>
                    {if $can_update}
                        <th class="left" width="1%">
                            {include file="common/check_items.tpl"}
                        </th>
                    {/if}
                    <th width="60%">{__("name")}</th>
                    <th width="30%">{__("code")}</th>
                    {if $can_update}
                        <th width="5%">&nbsp;</th>
                    {/if}
                </tr>
                </thead>
                <tbody>
                {foreach from=$documents item="document"}
                    <tr class="cm-row-item">
                        {if $can_update}
                            <td class="left">
                                <input type="checkbox" name="document_id[]" value="{$document->getId()}" class="checkbox cm-item" />
                            </td>
                        {/if}
                        <td>
                            <div class="object-group-link-wrap">
                                <a href="{"documents.update?document_id=`$document->getId()`"|fn_url}">{$document->getName()}</a>
                            </div>
                        </td>
                        <td>
                            <span class="block">{$document->getType()}.{$document->getCode()}</span>
                        </td>
                        <td class="nowrap">
                            <div class="hidden-tools">
                                {capture name="tools_list"}
                                    <li>{btn type="list" text=$edit_link_text href="documents.update?document_id=`$document->getId()`"}</li>
                                    <li>{btn type="text" text=__("export") href="documents.export?document_id=`$document->getId()`" method="POST"}</li>
                                {/capture}
                                {dropdown content=$smarty.capture.tools_list}
                            </div>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}
    <!--documents_list--></div>
</form>
{/capture}

{capture name="import_form"}
    <div class="install-addon">
        <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="import_documents" enctype="multipart/form-data">
            <div class="install-addon-wrapper">
                <img class="install-addon-banner" src="{$images_dir}/addon_box.png" width="151" height="141" />
                {include file="common/fileuploader.tpl" var_name="filename[]" allowed_ext="xml"}
            </div>
            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" but_text=__("import") but_name="dispatch[documents.import]" cancel_action="close"}
            </div>
        </form>
    </div>
{/capture}

{capture name="buttons"}
    {capture name="tools_items"}
        <li>{btn type="list" text=__("export_selected") dispatch="dispatch[documents.export]" form="manage_documents_form"}</li>

        {if fn_check_permissions("documents", "import", "admin", "POST")}
            <li>{include file="common/popupbox.tpl" id="import_form" link_text=__("import") act="link" link_class="cm-dialog-auto-size"  text=__("import") content=$smarty.capture.import_form general_class="action-btn"}</li>
        {/if}
    {/capture}

    {dropdown content=$smarty.capture.tools_items}
{/capture}

{include file="common/mainbox.tpl" title=__("documents") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar}
