<div id="upload_upgrade_package_container" class="install-addon">
    <form action="{""|fn_url}" method="post" name="upgrade_package_upload_form" class="form-horizontal" enctype="multipart/form-data">
        <div class="install-addon-wrapper">
            <img class="install-addon-banner" src="{$images_dir}/addon_box.png" width="151px" height="141px" />
            
            <p class="install-addon-text">{__("install_upgrade_package_text", ['[exts]' => implode(',', $config.allowed_pack_exts)])}</p>
            {include file="common/fileuploader.tpl" var_name="upgrade_pack[0]"}
        </div>

        <div class="buttons-container">
            {include file="buttons/save_cancel.tpl" but_name="dispatch[upgrade_center.upload]" cancel_action="close" but_text=__("upload")}
        </div>
    </form>
<!--upload_upgrade_package_container--></div>
