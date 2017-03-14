<div class="install-addon">

<form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="import_locations" enctype="multipart/form-data">

    <div class="install-addon-wrapper">
        <img class="install-addon-banner" src="{$images_dir}/addon_box.png" width="151" height="141" />

        {include file="common/fileuploader.tpl" var_name="filename[0]" allowed_ext="xml"}

    </div>

    <div class="control-group">
        <div class="controls">
            <label class="radio" for="sw_import_style_options_suffix_create">
            <input type="radio" id="sw_import_style_options_suffix_create" name="import_style" value="create" checked="checked" class="cm-switch-availability cm-switch-visibility cm-switch-inverse" />
            {__("create_new_layout")}</label>

            <label class="radio" for="sw_import_style_options_suffix_update">
            <input type="radio" id="sw_import_style_options_suffix_update" name="import_style" value="update" class="cm-switch-availability cm-switch-visibility"/>
            {__("update_current_layout")}</label>

            <input type="hidden" name="clean_up" value="N" />
            <input type="hidden" name="override_by_dispatch" value="N" />

            <div class="hidden shift-left" id="import_style_options">
                <label class="checkbox" for="elm_clean_up_export">
                <input id="elm_clean_up_export" type="checkbox" name="clean_up" value="Y" />
                {__("clean_up_all_locations_on_import")}</label>
                <label class="checkbox" for="elm_override_by_dispatch">
                <input id="elm_override_by_dispatch" type="checkbox" name="override_by_dispatch" value="Y" checked="checked" />
                {__("override_by_dispatch")}</label>
            </div>
        </div>
    </div>

    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl" but_text=__("import") but_name="dispatch[block_manager.import_layout]" cancel_action="close"}
    </div>
</form>

</div>
