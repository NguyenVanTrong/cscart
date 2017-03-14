<div class="control-group">
    <label class="control-label" for="ftp_host">{__("host")}:</label>
    <div class="controls">
        <input type="text" name="change_ftp_settings[ftp_hostname]" id="ftp_host" size="10" value="{$uc_settings.ftp_hostname}" class="input-medium" />
    </div>
</div>
 <div class="control-group">
    <label class="control-label" for="ftp_user">{__("username")}:</label>
    <div class="controls">
        <input type="text" name="change_ftp_settings[ftp_username]" id="ftp_user" size="10" value="{$uc_settings.ftp_username}" class="input-medium" />
    </div>
</div>
 <div class="control-group">
    <label class="control-label" for="ftp_password">{__("password")}:</label>
    <div class="controls">
        <input type="password" name="change_ftp_settings[ftp_password]" id="ftp_password" size="10" value="{$uc_settings.ftp_password}" class="input-medium" />
    </div>
</div>
 <div class="control-group">
    <label class="control-label" for="ftp_directory">{__("directory")}:</label>
    <div class="controls">
        <input type="text" name="change_ftp_settings[ftp_directory]" id="ftp_directory" size="10" value="{$uc_settings.ftp_directory}" class="input-medium" />
    </div>
</div>
 <div class="buttons-container buttons-bg">
    {include file="buttons/button.tpl" but_name="dispatch[upgrade_center.install]" but_text=__("auto_set_permissions_via_ftp") but_role="button_main" but_meta="btn-primary cm-ajax "}
</div>

<script type="text/javascript">
    (function(_, $) {
        $.ceEvent('on', 'ce.formajaxpost_upgrade_form_{$type}_{$id}', function(response, params){
            $.ceDialog('last_close');
            $.ceDialog('clear_stack');
        });
    }(Tygh, jQuery));
</script>