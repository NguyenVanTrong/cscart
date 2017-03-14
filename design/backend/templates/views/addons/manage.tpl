{include file="views/profiles/components/profiles_scripts.tpl" states=1|fn_get_all_states}

{if $runtime.company_id}
    {assign var="hide_for_vendor" value=true}
{/if}

{script src="js/tygh/tabs.js"}
{script src="js/tygh/filter_table.js"}
{script src="js/tygh/fileuploader_scripts.js"}

{capture name="mainbox"}

{capture name="sidebar"}
    {hook name="addons:manage_sidebar"}
    {include file="views/addons/components/addons_search_form.tpl" dispatch="addons.manage"}
    <div class="sidebar-row marketplace">
        <h6>{__("marketplace")}</h6>
        <p class="marketplace-link">{__("marketplace_find_more", ["[href]" => $config.resources.marketplace_url])}</p>
    </div>
    {if $snapshot_exist && !$hide_for_vendor}
        <div class="sidebar-row">
            <h6>{__("change_addons_initialization")}</h6>
            <ul class="unstyled sidebar-stat" id="addons_counter">
                <li>{__("tools_addons_installed_count")} <span><a href="{"addons.manage&type=installed"|fn_url}">{$addons_counter.installed}</a></span></li>
                <li>{__("tools_addons_activated_count")} <span><a href="{"addons.manage&type=active"|fn_url}">{$addons_counter.activated}</a></span></li>
                {*<li>{__("tools_addons_core_addons_count")} <span><a href="{"addons.manage&source=core"|fn_url}">{$addons_counter.core}</a></span></li>*}
                <li>{__("tools_addons_other_addons_count")} <span><a href="{"addons.manage&source=third_party"|fn_url}">{$addons_counter.other}</a></span></li>
            <!--addons_counter--></ul>
        </div>
        <hr>
        <div class="sidebar-row">
            <p>{__("change_addons_initialization_description")}</p>
            <div>
                <form action="{""|fn_url}" method="post">
                    <input type="hidden" name="dispatch" value="addons.tools">
                    <button type="submit" class="btn btn-block {if $settings.init_addons == "none"}disabled{/if}" {if $settings.init_addons == "none"}disabled="disabled" {/if}name="init_addons" value="none">{__("tools_addons_disable_all")}</button>
                    <button type="submit" class="btn btn-block {if $settings.init_addons == "core"}disabled{/if}" {if $settings.init_addons == "core"}disabled="disabled" {/if}name="init_addons" value="core">{__("tools_addons_disable_third_party")}</button>
                </form>
            </div>
        </div>
    {/if}

    {/hook}
{/capture}

{capture name="upload_addon"}
    {include file="views/addons/components/upload_addon.tpl"}
{/capture}

<div class="items-container" id="addons_list">
{hook name="addons:manage"}

{if ($search.type == 'any' && empty($search.source)) || in_array($search.type, ['installed', 'active', 'disabled'])}
    {$show_installed_tab=true}
{/if}

<div class="tabs cm-j-tabs clear">
    <ul class="nav nav-tabs">
        <li id="tab_installed_addons" class="cm-js{if $show_installed_tab} active{/if}"><a>{__("installed_addons")}</a></li>
        <li id="tab_browse_all_available_addons" class="cm-js{if !$show_installed_tab} active{/if}"><a>{__("browse_all_available_addons")}</a></li>
    </ul>
</div>

<div class="cm-tabs-content">
    <div id="content_tab_installed_addons" {if !$show_installed_tab}class="hidden"{/if}>
        {if $settings.init_addons}
            <div class="alert alert-block addon-info-msg">
                <span>{__("tools_addons_disabled_msg")}</span>
                <form action="{""|fn_url}" method="post">
                    <input type="hidden" name="dispatch" value="addons.tools">
                    <button type="submit" class="btn btn-warning" name="init_addons" value="restore">{__("tools_re_enable_add_ons")}</button>
                </form>
            </div>
        {/if}
        {include file="views/addons/components/addons_list.tpl" show_installed=true}
    </div>
    <div id="content_tab_browse_all_available_addons" {if $show_installed_tab}class="hidden"{/if}>
        {include file="views/addons/components/addons_list.tpl"}
    </div>
</div>

{/hook}
<!--addons_list--></div>

{capture name="adv_buttons"}
    {hook name="addons:adv_buttons"}
    {if !$runtime.company_id && !"RESTRICTED_ADMIN"|defined}
        {include file="common/popupbox.tpl" id="upload_addon" text=__("upload_addon") title=__("upload_addon") content=$smarty.capture.upload_addon act="general" link_class="cm-dialog-auto-size" icon="icon-plus" link_text=""}
    {/if}
    {/hook}
{/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("addons") content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons}
