{$permissions_count = $data|count}
{$show_more = false}
{if $permissions_count > 7 }
    {$show_more = true}
{/if}

{if $data == "ok"}
<div class="upgrade-center_notice success">
    <div class="upgrade-center_notice-msg">
        <span>{__("upgrade_center_permission_adjusted_properly")}</span>
    </div>
</div>
{else}
    <div class="upgrade-center_notice">
        <!-- upgrade-center_warning or upgrade-center_error -->
        <div class="upgrade-center_error">
            <div class="upgrade-center_set_ftp pull-right">
                <a href="{"upgrade_center.ftp_settings?package_id=`$id`&package_type=`$type`"|fn_url}" class="btn cm-dialog-keep-in-place cm-dialog-opener" data-ca-target-id="auto_set_permissions_{$id}">{__("auto_set_permissions_via_ftp")}</a>
            </div>
            <h4>{__("permissions_issue")}</h4>
            <p>{__("text_uc_non_writable_files")}</p>
        </div>
        <div class="upgrade-center_notice-table {if !$show_more} all{/if}" id="files_list">
            <table class="table table-condensed">
                <thead>
                    <tr>
                        <th>{__("file")}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $data as $filename}
                    <tr>
                        <td colspan="2">{$filename}</td>
                    </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
        <div id="auto_set_permissions_{$id}" title="{__("ftp_server_options")}" class="upgrade-center_auto_set_permissions">
        </div>
        {if $show_more}
            <div class="upgrade-center_notice-btn" id="toggle_more_files">
                <a class="btn show-more">{__("show_more")}</a>
                <a class="btn show-less">{__("show_less")}</a>
            </div>
        {/if}
    </div>
{/if}
{if $show_more}
    <script type="text/javascript">
    (function(_, $) {
        $('#toggle_more_files').on('click', function() {
            $(this).toggleClass('more');
            $('#files_list').toggleClass('all');
        });
    }(Tygh, Tygh.$));
    </script>
{/if}