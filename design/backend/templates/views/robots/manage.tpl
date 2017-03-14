{script src="js/tygh/tabs.js"}

{capture name="mainbox"}

{assign var="r_url" value=$config.current_url|escape:url}

<div class="items-container" id="manage_robots">

<form id="robots_form" action="{""|fn_url}" method="post" name="robots_update_form" class="form-horizontal form-edit cm-ajax cm-disable-empty-files">
    <input type="hidden" name="result_ids" value="manage_robots" />

    <div id="manage_robots_content">
    
    <div class="control-group">
        <label for="elm_robots_edit" class="control-label">{__("edit_robots")}:</label>
        <div class="controls">
            <input type="checkbox" name="robots_data[edit]" id="elm_robots_edit" value="Y"{if $edit} checked="checked"{/if} />
        </div>
    </div>

    <div class="control-group">
        <div class="controls">
            <a href="{"robots.manage?default=1"|fn_url}" class="btn cm-ajax" data-ca-target-id="manage_robots_content">{__("restore_robots")}</a>
        </div>
    </div>
    
    <div class="control-group">
        <div class="controls">
            <textarea id="elm_robots_robots" name="robots_data[content]" cols="55" rows="12" class="input-large"{if !$edit} readonly="readonly"{/if}>{$robots}</textarea>
        </div>
    </div>
    
    <!--manage_robots_content--></div>

    {if $smarty.request.is_not_writable}
        {include file="common/subheader.tpl" title=__("ftp_server_options")}
        <div class="control-group">
            <label for="host" class="control-label">{__("host")}:</label>
            <div class="controls">
                <input id="host" type="text" name="ftp_access[ftp_hostname]" size="30" value="{$ftp_access.ftp_hostname}" class="input-text" />
            </div>
        </div>

        <div class="control-group">
            <label for="login" class="control-label">{__("login")}:</label>
            <div class="controls">
                <input id="login" type="text" name="ftp_access[ftp_username]" size="30" value="{$ftp_access.ftp_username}" class="input-text" />
            </div>
        </div>

        <div class="control-group">
            <label for="password" class="control-label">{__("password")}:</label>
            <div class="controls">
                <input id="password" type="password" name="ftp_access[ftp_password]" size="30" value="{$ftp_access.ftp_password}" class="input-text" />
            </div>
        </div>

        <div class="control-group">
            <label for="base_path" class="control-label">{__("ftp_directory")}:</label>
            <div class="controls">
                <input id="base_path" type="text" name="ftp_access[ftp_directory]" size="30" value="{$ftp_access.ftp_directory}" class="input-text" />
            </div>
        </div>

        <div class="buttons-container">
            {include file="buttons/button.tpl" but_role="submit" but_text=__("recheck") but_name="dispatch[robots.check]" but_meta=" "}
            {include file="buttons/button.tpl" but_role="submit" but_text=__("upload_via_ftp") but_name="dispatch[robots.update_via_ftp]"}
        </div>
    {/if}

</form>

<script type="text/javascript">
    (function(_, $){
        var default_value = '';
        $(_.doc).on('click', '#elm_robots_edit', function(e){
            var jelm = $(this),
                checked = jelm.is(':checked'),
                target = jelm.parents('form').find('#elm_robots_robots');
            if (checked) {
                target.removeAttr('readonly');
                default_value = target.val();
            } else {
                target.attr('readonly', 'readonly');
                target.val(default_value);
            }
        });
    })(Tygh, Tygh.$);
</script>

<!--manage_robots--></div>

{capture name="buttons"}
    {include file="buttons/save.tpl" but_name="dispatch[robots.update]" but_role="submit-link" but_target_form="robots_update_form"}
{/capture}

{/capture}

{include file="common/mainbox.tpl" title=__("robots_title") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons}
