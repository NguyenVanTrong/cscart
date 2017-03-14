{capture name="mainbox"}

{capture name="tabsbox"}
    <div class="upgrade-center" id="content_packages">
        <a id="popup_timeout_check_failed_link" class="cm-dialog-opener cm-dialog-auto-size hidden" data-ca-target-id="popup_timeout_check_failed"></a>
        
        <div class="hidden upgrade-center_wizard cm-dialog-auto-size {if $timeout_check_failed} cm-dialog-auto-open{/if}" id="popup_timeout_check_failed" title="{__("upgrade_center.warning_msg_timeout_fail")}">
            <div class="upgrade_center_wizard-msg">
                <p class="text-error lead">
                    {__("upgrade_center.warning_msg_timeout_check_failed") nofilter}
                </p>
            </div>
            <div class="buttons-container">
                {include file="buttons/save_cancel.tpl" cancel_action="close" hide_first_button=true}
            </div>
        </div>

        {foreach $upgrade_packages as $type => $packages}
            {foreach $packages as $_id => $package}
                {$id = $_id|replace:".":"_"}
                <div class="upgrade-center_package">
                    <form name="upgrade_form_{$type}_{$id}" method="post" action="{fn_url()}" class="form-horizontal form-edit cm-disable-check-changes">
                        <input type="hidden" name="type" value="{$type}">
                        <input type="hidden" name="id" value="{$_id}">
                        <input type="hidden" name="result_ids" value="install_notices_{$id},install_button_{$id}">

                        <div class="hidden upgrade-center_wizard" id="content_upgrade_center_wizard_{$id}" title="{__("warning")}">
                            <div class="upgrade_center_wizard-msg">
                                <p class="text-error lead">
                                    {__("upgrade_center.warning_msg_upgrade_is_complicated") nofilter}
                                </p>
                                <blockquote>
                                    <p>{__("upgrade_center.warning_msg_specialists", ['[upgrade_center_specialist]'=>$config.resources.upgrade_center_specialist_url, '[upgrade_center_team]'=>$config.resources.upgrade_center_team_url])}</p>
                                    <br>
                                    <p>{__("upgrade_center.warning_msg_third_party_add_ons")}</p>
                                    <br>
                                    <p>{__("upgrade_center.warning_msg_test_local")}</p>
                                    <br>
                                    <p>{__("upgrade_center.warning_msg_after_upgrade")}</p>
                                    <br>
                                    <p>{__("upgrade_center.warning_msg_generally")}<br><br>                                
                                        <input type="submit" name="dispatch[upgrade_center.check_timeout]" class="upgrade-center_check_timeout btn cm-ajax cm-comet cm-post" value="{__("check_php_timeout")}">
                                    </p>
                                    <br>
                                </blockquote>
                            </div>
                            <div class="buttons-container">
                                {if "DEVELOPMENT"|defined && $smarty.const.DEVELOPMENT}
                                <label class="pull-left">
                                    <input id="skip_backup" type="checkbox" name="skip_backup" value="Y" />
                                    {__("upgrade_center.skip_backup")}
                                </label>
                                {/if}
                                <div class="btn-group btn-hover dropleft">
                                    <input type="submit" name="dispatch[upgrade_center.install]" class="btn btn-primary cm-ajax cm-comet cm-dialog-closer" value="{__("i_agree_continue")}">
                                </div>
                            </div>
                        </div>

                        <div class="upgrade-center_item">
                            <div class="upgrade-center_icon">
                                {if $type == "core" || $type == "hotfix"}
                                    <i class="glyph-physics1"></i>
                                {else}
                                    <i class="glyph-addon"></i>
                                {/if}
                            </div>

                            <div class="upgrade-center_content">
                                <h4 class="upgrade-center_title">{$package.name}</h4>
                                <ul class="upgrade-center_info">
                                    <li> <strong>{__("new_version")}:</strong> {$package.to_version}</li>
                                    <li> <strong>{__("release_date")}:</strong> {$package.timestamp|date_format}</li>
                                    <li> <strong>{__("filesize")}:</strong> {$package.size|formatfilesize nofilter}</li>
                                </ul>
                                <p class="upgrade-center_desc">
                                    {$package.description|replace:"\n":"<br>" nofilter}
                                </p>

                                {if $package.ready_to_install}
                                    {include file="views/upgrade_center/components/install_button.tpl" id=$id caption=__("install")}

                                    <a class="upgrade-center_pkg cm-dialog-opener cm-ajax" href="{"upgrade_center.package_content?package_id=`$_id`"|fn_url}" data-ca-target-id="package_content_{$id}" data-ca-dialog-title="{$package.name|escape}">{__("show_package_contents")}</a>

                                {else}
                                    <div class="upgrade-center_install">
                                        <input name="dispatch[upgrade_center.download]" type="submit" class="btn cm-loading-btn" value="{__("download")}" data-ca-loading-text="{__("loading")}">
                                    </div>
                                {/if}
                                    
                                {include file="views/upgrade_center/components/notices.tpl" id=$id type=$type}
                            </div>
                        </div>
                    </form>
                </div>
            {/foreach}
        {foreachelse}
            <p class="no-items">{__('text_no_upgrades_available')}</p>
        {/foreach}
    <!--content_packages--></div>

    <div class="upgrade-center hidden" id="content_installed_upgrades">
        {foreach $installed_packages as $_id => $package}
            <div class="upgrade-center_item">
                <div class="upgrade-center_icon">
                    {if $package.type == "core" || $package.type == "hotfix"}
                        <i class="glyph-physics1"></i>
                    {else}
                        <i class="glyph-addon"></i>
                    {/if}
                </div>

                <div class="upgrade-center_content">
                    <h4 class="upgrade-center_title">{$package.name}</h4>
                    <ul class="upgrade-center_info">
                        <li> <strong>{__("upgraded_on")}:</strong> {$package.timestamp|date_format}</li>
                    </ul>
                    <p class="upgrade-center_desc">
                        {$package.description|replace:"\n":"<br>" nofilter}
                    </p>

                    {if !empty($package.conflicts)}
                        <div class="upgrade-center_install">
                            <a class="upgrade-center_pkg cm-dialog-opener cm-ajax btn" href="{"upgrade_center.conflicts?package_id=`$package.id`"|fn_url}" data-ca-target-id="conflicts_content_{$package.id}" data-ca-dialog-title="{$package.name|escape}">{__("local_modifications")}</a>
                        </div>
                    {/if}
                </div>

            </div>
        {foreachelse}
            <p class="no-items">{__('no_data')}</p>
        {/foreach}
    <!--content_installed_upgrades--></div>
    {literal}
    <script type="text/javascript">

        (function(_, $){
            $('.cm-loading-btn').on('click', function() {
                var self = $(this);
                setTimeout(function() {
                    self.prop('value', self.data('caLoadingText'));
                    $('.cm-loading-btn').attr('disabled', true);
                }, 50);
                return true;
            });

            $('.upgrade-center_check_timeout').on('click', function() {
                var timer;
                var millisecBeforeShowMsg = 365000;

                $.ceEvent('on', 'ce.progress_init', function(o) {
                    timer = window.setTimeout(function() {
                        $.toggleStatusBox('hide');
                        $.ceDialog('get_last').ceDialog('close');
                        $('#popup_timeout_check_failed_link').trigger('click');
                        $('#comet_control, .modal-backdrop').remove();
                    }, millisecBeforeShowMsg);
                });

                $.ceEvent('on', 'ce.progress_finish', function(o) {
                    if(timer) {
                        window.clearTimeout(timer);
                        timer = null;
                    }
                });
            });

        })(Tygh, Tygh.$);
    </script>
    {/literal}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$selected_section track=true}

{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        <li>{btn type="list" text=__("refresh_packages_list") href="upgrade_center.refresh"}</li>
        <li>{btn type="list" text=__("settings") href="settings.manage&section_id=Upgrade_center"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
    {$smarty.capture.install_btn nofilter}
    {if $installed_upgrades.has_upgrades}
        {include file="buttons/button.tpl" but_href="upgrade_center.installed_upgrades" but_text=__("installed_upgrades") but_role="link"}
    {/if}
{/capture}

{capture name="upload_upgrade_package"}
    {include file="views/upgrade_center/components/upload_upgrade_package.tpl"}
{/capture}

{capture name="adv_buttons"}
    {hook name="upgrade_center:adv_buttons"}
        {include file="common/popupbox.tpl" id="upload_upgrade_package_container" text=__("upload_upgrade_package") title=__("upload_upgrade_package") content=$smarty.capture.upload_upgrade_package act="general" link_class="cm-dialog-auto-size" icon="icon-plus" link_text=""}
    {/hook}
{/capture}

{include file="common/mainbox.tpl" title=__("upgrade_center") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar}
