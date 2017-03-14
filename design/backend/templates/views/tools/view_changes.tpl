{capture name="diff_legend"}
    <div class="diff-legend">
        {if $check_types.A}
            <span class="label label-success">{__("file_changes_detector.added")}</span>
        {/if}
        {if $check_types.C}
            <span class="label label-warning">{__("file_changes_detector.changed")}</span>
        {/if}
        {if $check_types.D}
            <span class="label label-important">{__("file_changes_detector.deleted")}</span>
        {/if}
    </div>
{/capture}

{capture name="mainbox"}
    {literal}
    <style type="text/css">
        .diff-legend {
            margin: 20px 20px 20px 0px;
        }
        .diff-legend .label {
            margin-right: 10px;
        }
        .snapshot-added {
            background-color: #dff0d8 !important;
        }
        .snapshot-changed {
            background-color: #fcdca4 !important;
        }
        .snapshot-deleted {
            background-color: #f2dede !important;
        }
    </style>
    {/literal}


    <div class="items-container multi-level">
        {if $changes_tree}
            <div class="alert alert-block">
                <p>{__("modified_core_files_found", ['[product]' => $smarty.const.PRODUCT_NAME])}</p>
            </div>

            {include file="views/tools/components/changes_tree.tpl" parent_id=0 show_all=true expand_all=true}
        {else}
            <p class="no-items">{__("no_modified_core_files_found")}</p>
        {/if}
    </div>

    {if $db_diff}
        {include file="common/subheader.tpl" title=__("database_structure_changes")}
        <pre style="height: 400px; overflow-y: scroll" class="diff-container">{$db_diff nofilter}</pre>
    {/if}

    {** include fileuploader **}
    {*include file="common/file_browser.tpl"*}
    {** /include fileuploader **}

    <form action="{""|fn_url}" method="post" name="data_compare_form" enctype="multipart/form-data" class="form-horizontal form-edit">
        {if $config.tweaks.show_database_changes}

            {include file="common/subheader.tpl" title=__("database_data_changes")}

            <div class="control-group">
                <label class="control-label" for="name_db" >{__("db_name")}</label>
                <div class="controls">
                    <input type="text" name="compare_data[db_name]" id="name_db" value="" class="span4" />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="type_base_file">{__("file")}</label>
                <div class="controls">
                    {if $compare_data.file_path}
                        <b>{$compare_data.file_path}</b> ({$compare_data.file_size|formatfilesize nofilter})
                    {/if}
                {include file="common/fileuploader.tpl" var_name="base_file"}
                </div>
            </div>
        {/if}

        {capture name="buttons"}
            {if !$dist_filename}
                <a class="btn" href="{"tools.create_snapshot?redirect_url={$config.current_url|escape:url}"|fn_url}">{__("scan_for_modified_core_files")}</a>
            {/if}

	        {if $config.tweaks.show_database_changes}
    	    	{include file="buttons/button.tpl" but_text=__("compare") but_role="submit-link" but_target_form="data_compare_form" but_name="dispatch[tools.view_changes]"}
			{/if}
        {/capture}
    </form>

    {if $db_d_diff}
        <pre style="height: 300px; overflow-y: scroll" class="diff-container">{$db_d_diff nofilter}</pre>
    {/if}

    {if $changes_tree || $db_diff || $db_d_diff}
        {$smarty.capture.diff_legend nofilter}
    {/if}

    {capture name="sidebar"}
        <div class="sidebar-row">
            <h6>{__("last_scan_time")}</h6>
            <p>
                {if $dist_filename}
                    <span class="muted">{__("file_changes_detector.snapshot_not_found", ['[dist_filename]' => $dist_filename])}</span>
                {else}
                    {if $creation_time}<span class="muted">{$creation_time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span>{/if}
                {/if}
            </p>
            <hr />
        </div>
    {/capture}

    {$changes_tree_keys=$changes_tree|array_keys}
    <script type="text/javascript">
        Tygh.$(document).ready(function(){ldelim}
          //  alert('#on_changes_{$changes_tree_keys.0}');
            Tygh.$('#on_changes_{$changes_tree_keys.0}').click();
        {rdelim}
        );
    </script>
{/capture}

{include file="common/mainbox.tpl" title=__("file_changes_detector") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar}
