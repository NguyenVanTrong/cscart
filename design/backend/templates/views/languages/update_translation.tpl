{$id = $lang_data.lang_id}

<div id="content_group{$id}_variables" class="install-addon">

    <form action="{""|fn_url}" method="post" name="update_language_translations_{$id}" class="form-horizontal{if !""|fn_allow_save_object:"languages"} cm-hide-inputs{/if}" enctype="multipart/form-data">
        <input type="hidden" name="language_data[lang_code]" value="{$lang_data.lang_code}">


        <div class="install-addon-wrapper">
            <img class="install-addon-banner" src="{$images_dir}/addon_box.png" width="151px" height="141px" />
            
            {include file="common/fileuploader.tpl" var_name="language_data[po_file]" prefix="variables_`$id`" allowed_ext="po, zip"}

        </div>


        {if ""|fn_allow_save_object:"languages"}
            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" but_name="dispatch[languages.update_translation]" but_text=__("update") cancel_action="close"}
            </div>
        {/if}
    </form>

<!--content_group{$id}_variables--></div>