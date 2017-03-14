{assign var="return_url" value=$config.current_url}

{capture name="mainbox"}
{capture name="tabsbox"}

{assign var="can_update" value=fn_check_permissions('snippets', 'update', 'admin', 'POST')}
{assign var="edit_link_text" value=__("edit")}

{if !$can_update}
    {assign var="edit_link_text" value=__("view")}
{/if}

{foreach from=$groups item="group" key="group_id"}

<div id="content_email_templates_{$group_id}" {if $group_id != "C"}class="hidden"{/if}>
<div class="items-container">
    <table class="table table-middle table-objects">
        <tbody>
            {foreach from=$group item="email_template"}
                {include file="common/object_group.tpl"
                    id_prefix=$group_id
                    id=$email_template->getId()
                    text=$email_template->getName()
                    status=$email_template->getStatus()
                    href="email_templates.update?template_id=`$email_template->getId()`"
                    object_id_name="template_id"
                    table="template_emails"
                    href_delete=""
                    delete_target_id=""
                    skip_delete=true
                    header_text="{__("editing_email_template")}: `$email_template->getName()`"
                    no_popup=true
                    no_table=true
                    draggable=false
                    link_text=$edit_link_text
                    nostatus=!$can_update
                }
            {/foreach}
        </tbody>
    </table>
</div>
<!--content_email_templates_{$group_id}--></div>
{/foreach}

<div class="hidden" id="content_snippets">
        {include file="views/snippets/components/list.tpl"
            snippets=$snippets
            result_ids="content_snippets"
            return_url=$return_url
        }
<!--content_snippets--></div>

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox}


{capture name="import_form"}
    <div class="install-addon">
        <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="import_email_templates" enctype="multipart/form-data">
            <div class="install-addon-wrapper">
                <img class="install-addon-banner" src="{$images_dir}/addon_box.png" width="151" height="141" />
                {include file="common/fileuploader.tpl" var_name="filename[]" allowed_ext="xml"}
            </div>
            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" but_text=__("import") but_name="dispatch[email_templates.import]" cancel_action="close"}
            </div>
        </form>
    </div>
{/capture}

{capture name="buttons"}
    {capture name="tools_items"}
        <li>{btn type="text" href="email_templates.export" text=__("export") method="POST"}</li>

        {if fn_check_permissions("email_templates", "import", "admin", "POST")}
            <li>{include file="common/popupbox.tpl" id="import_email_templates_form" link_text=__("import")  text=__("import") content=$smarty.capture.import_form  act="link" link_class="cm-dialog-auto-size" general_class="action-btn"}</li>
        {/if}
    {/capture}

    {include file="views/snippets/components/tools_list.tpl" additional_snippet_tools_list_items=$smarty.capture.tools_items}

    {dropdown content=$smarty.capture.tools_items class="cm-tab-tools hidden" id="tools_email_templates_C"}
    {dropdown content=$smarty.capture.tools_items class="cm-tab-tools hidden" id="tools_email_templates_A"}
{/capture}

{capture name="adv_buttons"}
    {include file="views/snippets/components/adv_buttons.tpl" result_ids="content_snippets" return_url=$return_url type='mail' addon=''}
{/capture}

{/capture}
{include file="common/mainbox.tpl"
    title=__("email_templates")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    adv_buttons=$smarty.capture.adv_buttons
}
