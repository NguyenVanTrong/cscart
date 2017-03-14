{$collisions_count = $data.changed|count + $data.deleted|count + $data.new|count}
{$show_more = false}
{if $collisions_count > 7 }
    {$show_more = true}
{/if}
<div class="upgrade-center_notice">
    
    <div class="upgrade-center_error">
        <h4>{__("text_uc_local_modification")}</h4>
        <p>{__("text_uc_changed_files_message")}</p>
    </div>

    <div class="upgrade-center_notice-table {if !$show_more} all{/if}" id="files_list">
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th>{__("files")}</th>
                    <th class="right">{__("action")}</th>
                </tr>
            </thead>
            <tbody>
            {foreach $data as $status => $files}
                {foreach $files as $file_path}
                    <tr>
                        <td>
                            {$file_path}
                        </td>
                        <td width="10%" class="right">
                            {if $status == "changed" || $status == "new"}
                                <span class="label label-warning">{__("text_uc_will_be_changed")}</span>
                            {elseif $status == "deleted"}
                                <span class="label label-important">{__("text_uc_will_be_deleted")}</span>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            {/foreach}
            </tbody>
        </table>
    </div>
    {if $show_more}
        <div class="upgrade-center_notice-btn" id="toggle_more_files">
            <a class="btn show-more">{__("show_more")}</a>
            <a class="btn show-less">{__("show_less")}</a>
        </div>
    {/if}
</div>

<div class="checkbox upgrade-center_notice-agree">
    <label class="cm-required" for="skip_collisions_{$id}">{__("text_uc_agreed_collisions")}</label>
    <input type="checkbox" id="skip_collisions_{$id}" name="skip_collisions" value="Y">
</div>

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