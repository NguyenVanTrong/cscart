{script src="js/tygh/tabs.js"}

{capture name="mainbox"}

    {notes title=__("current_database_size")}
        <p><span>{$database_size|number_format}</span> {__("bytes")}</p>
    {/notes}
    <div id="backup_management">
        <form action="{""|fn_url}" method="post" name="backups_form">
            {hook name="backups:manage"}
            {$result_ids = "backup_management,actions_panel"}
                <input type="hidden" name="result_ids" value="{$result_ids}"/>
            {if $backup_files}
                <table class="table table-middle">
                    <thead>
                    <tr>
                        <th width="1%">
                            {include file="common/check_items.tpl"}</th>
                        <th>{__("filename")}</th>
                        <th>{__("date")}</th>
                        <th>{__("filesize")}</th>
                        <th>{__("type")}</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>

                    {foreach from=$backup_files item=file key=name}
                        <tr>
                            <td><input type="checkbox" name="backup_files[]" value="{$name}" class="checkbox cm-item"/>
                            </td>
                            <td><a href="{"datakeeper.getfile?file=`$name`"|fn_url}"><span>{$name}</span></a></td>
                            <td>{$file.create}</td>
                            <td>{$file.size|number_format}&nbsp;{__("bytes")}</td>
                            <td>{__($file.type)}</td>
                            <td class="nowrap">
                                <div class="hidden-tools">
                                    {capture name="tools_list"}
                                        <li>{btn type="list" text=__("download") href="datakeeper.getfile?file=`$name`"}</li>
                                        {if $file.can_be_restored}
                                            <li>{btn type="list" class="cm-confirm" text=__("restore") href="datakeeper.restore?backup_file=`$name`" method="POST"}</li>
                                        {/if}
                                        <li class="divider"></li>
                                        <li>{btn type="list" class="cm-confirm cm-ajax" text=__("delete") href="datakeeper.delete?backup_file=`$name`" data=["data-ca-target-id" => $result_ids] method="POST"}</li>
                                    {/capture}
                                    {dropdown content=$smarty.capture.tools_list}
                                </div>
                            </td>
                        </tr>
                    {/foreach}
                </table>
            {else}
                <p class="no-items">{__("no_items")}</p>
            {/if}
            {/hook}
        </form>
        <!--backup_management--></div>
    {capture name="upload_backup"}
        {** UPLOAD BACKUP **}
        <div class="install-addon" id="content_upload_backup">
            <form action="{""|fn_url}" method="post" name="upload_backup_form" class="form-horizontal"
                  enctype="multipart/form-data">
                <input type="hidden" name="result_ids" value="theme_upload_container"/>

                <div class="install-addon-wrapper">
                    <img class="install-addon-banner" src="{$images_dir}/addon_box.png" width="151px" height="141px"/>
                    {include file="common/fileuploader.tpl" var_name="dump[0]" allowed_ext="zip,tgz,sql"}

                </div>

                <div class="buttons-container">
                    {include file="buttons/save_cancel.tpl" but_text=__("upload") but_name="dispatch[datakeeper.upload]" cancel_action="close" but_role="submit-link"}
                </div>
            </form>
        </div>
        {** /UPLOAD BACKUP **}
    {/capture}
    {include file="common/popupbox.tpl" id="upload_backup" text=__("upload_file") content=$smarty.capture.upload_backup link_class="cm-dialog-auto-size"}

    {capture name="buttons"}
        {capture name="tools_list"}
            <li>{btn type="list" text=__("logs") href="logs.manage"}</li>
            <li>{btn type="dialog" text=__("upload_file") target_id="content_upload_backup" form="upload_backup_form" class="cm-dialog-auto-size"}</li>
            <li><a href="{"datakeeper.optimize"|fn_url}" data-ca-target-id="elm_sidebar"
                   class="cm-post cm-comet cm-ajax">{__("optimize_database")}</a></li>
            {if $backup_files}
                <li class="divider"></li>
                <li>
                    {btn type="delete_selected" dispatch="dispatch[datakeeper.m_delete]" form="backups_form" class="cm-ajax"}
                </li>
            {/if}
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/capture}

    {capture name="adv_buttons"}
        {capture name="add_new_picker"}
            {** CREATE BACKUP **}
            <div id="content_backup">
                <form action="{""|fn_url}" method="post" name="backup_form"
                      class="form-horizontal form-edit cm-ajax cm-comet cm-form-dialog-closer">
                    <input type="hidden" name="selected_section" value="backup"/>
                    <input type="hidden" name="result_ids" value="{$result_ids}"/>

                    <div class="control-group">
                        <label class="control-label" for="elm_backup_files">{__("backup_files")}:</label>

                        <div class="controls">
                            <label class="checkbox">
                                <input type="hidden" name="backup_files" value="N"/>
                                <input type="checkbox" name="backup_files" id="elm_backup_files" value="Y"
                                       onclick="Tygh.$('#files_backup_options').toggleBy();"/>
                            </label>
                        </div>
                    </div>

                    <div id="files_backup_options" class="hidden">
                        <hr>
                        <div class="control-group">
                            <label for="extra_folders" class="control-label">{__("extra_folders")}:</label>

                            <div class="controls">
                                <select name="extra_folders[]" id="extra_folders" multiple="multiple" size="5">
                                    <option value="images">images</option>
                                    <option value="var/files">var/files</option>
                                    <option value="var/attachments">var/attachments</option>
                                    <option value="var/langs">var/langs</option>
                                </select>

                                <p><a onclick="Tygh.$('#extra_folders').selectOptions(true); return false;"
                                      class="underlined">{__("select_all")}</a> / <a
                                            onclick="Tygh.$('#extra_folders').selectOptions(false); return false;"
                                            class="underlined">{__("unselect_all")}</a></p>
                            </div>
                        </div>
                        <hr>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="elm_backup_database">{__("backup_data")}:</label>

                        <div class="controls">
                            <label class="checkbox">
                                <input type="hidden" name="backup_database" value="N"/>
                                <input type="checkbox" name="backup_database" id="elm_backup_database" value="Y"
                                       checked="checked" onclick="Tygh.$('#database_backup_options').toggleBy();"/>
                            </label>
                        </div>
                    </div>

                    <div id="database_backup_options">
                        <hr>
                        <div class="control-group">
                            <label for="dbdump_tables" class="control-label">{__("select_tables")}:</label>

                            <div class="controls">
                                <select name="dbdump_tables[]" id="dbdump_tables" multiple="multiple" size="10">
                                    {foreach from=$all_tables item=tbl}
                                        <option value="{$tbl}"{if $config.table_prefix == '' || $tbl|strpos:$config.table_prefix === 0} selected="selected"{/if}>{$tbl}</option>
                                    {/foreach}
                                </select>

                                <p><a onclick="Tygh.$('#dbdump_tables').selectOptions(true); return false;"
                                      class="underlined">{__("select_all")}</a> / <a
                                            onclick="Tygh.$('#dbdump_tables').selectOptions(false); return false;"
                                            class="underlined">{__("unselect_all")}</a></p>

                                <div class="muted">{__("multiple_selectbox_notice")}</div>
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="dbdump_filename" class="control-label">
                                {__("backup_options")}:
                            </label>

                            <div class="controls">
                                <label for="dbdump_schema" class="checkbox">
                                    <input type="checkbox" name="dbdump_schema" id="dbdump_schema" value="Y"
                                           checked="checked">
                                    {__("backup_schema")}
                                </label>
                                <label for="dbdump_data" class="checkbox">
                                    <input type="checkbox" name="dbdump_data" id="dbdump_data" value="Y"
                                           checked="checked">
                                    {__("backup_data")}
                                </label>
                            </div>
                        </div>
                        <hr>
                    </div>

                    <div class="control-group">
                        <label for="dbdump_filename" class="control-label">{__("backup_filename")}:</label>

                        <div class="controls">
                            <div class="input-append">
                                <input type="text" name="dbdump_filename" id="dbdump_filename" size="30"
                                       value="backup_{$smarty.const.PRODUCT_VERSION}_{"dMY_His"|date:$smarty.now}" class="input-text">
                                <span class="add-on">.zip</span>
                            </div>
                            <p class="muted">{__("text_backup_filename_hint")}</p>
                        </div>
                    </div>

                    <div class="buttons-container">
                        {include file="buttons/save_cancel.tpl" but_name="dispatch[datakeeper.backup]" cancel_action="close" but_role="submit-link" but_meta="cm-comet"}
                    </div>
                </form>
            </div>
            {** /CREATE BACKUP **}
        {/capture}
        {if $backup_create_allowed}
            {include file="common/popupbox.tpl" id="create_backup" text=__("create_backup") title=__("create_backup") content=$smarty.capture.add_new_picker act="create" icon="icon-plus"}
        {/if}
    {/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("backup_restore") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons}