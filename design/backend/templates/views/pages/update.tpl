{if $page_data.page_id}
    {assign var="id" value=$page_data.page_id}
{else}
    {assign var="id" value=0}
{/if}

{if $id && $page_type != $smarty.const.PAGE_TYPE_LINK && (!"ULTIMATE"|fn_allowed_for || $runtime.company_id)}
    {$view_uri = "pages.view?page_id=`$id`"|fn_get_preview_url:$page_data:$auth.user_id}
{/if}

{assign var="allow_save" value=true}
{if "ULTIMATE"|fn_allowed_for}
    {assign var="allow_save" value=$page_data|fn_allow_save_object:"pages"}
{/if}
{$show_save_btn = $allow_save scope = root}
{capture name="mainbox"}

{capture name="tabsbox"}

<form action="{""|fn_url}" method="post" name="page_update_form" class="form-horizontal form-edit  {if !$allow_save}cm-hide-inputs{/if}" enctype="multipart/form-data">

<div id="update_page_form_{$page_data.page_id}">
    <input type="hidden" class="cm-no-hide-input" id="selected_section" name="selected_section" value="{$selected_section}"/>
    <input type="hidden" class="cm-no-hide-input" id="page_id" name="page_id" value="{$id}" />
    <input type="hidden" class="cm-no-hide-input" name="page_data[page_type]" id="page_type" size="55" value="{$page_type}"/>
    <input type="hidden" class="cm-no-hide-input" name="come_from" value="{$come_from}" />
    <input type="hidden" class="cm-no-hide-input" name="result_ids" value="update_page_form_{$page_data.page_id}"/>

    <div id="content_basic">

    {include file="common/subheader.tpl" title=__("information") target="#pages_information_setting"}
    <div id="pages_information_setting" class="in collapse">
    <fieldset>
        {include file="views/pages/components/parent_page_selector.tpl"}

        <div class="control-group">
            <label for="elm_page_name" class="control-label cm-required">{__("name")}:</label>
            <div class="controls">
                <input type="text" name="page_data[page]" id="elm_page_name" size="55" value="{$page_data.page}" class="input-large" />
            </div>
        </div>

        {if $page_data.parent_id != 0 && $page_data.page_id != 0}
            {assign var="disable_company_picker" value=true}
        {/if}
        {if "MULTIVENDOR"|fn_allowed_for}
            {assign var="zero_company_id_name_lang_var" value="none"}
            {assign var="js_action" value="fn_change_vendor_for_page(elm);"}
        {/if}
        {include file="views/companies/components/company_field.tpl"
            name="page_data[company_id]"
            id="elm_page_data_company_id"
            zero_company_id_name_lang_var=$zero_company_id_name_lang_var
            selected=$page_data.company_id
            js_action=$js_action
            disable_company_picker=$disable_company_picker
        }

        {hook name="pages:detailed_description"}

        {if $page_type != $smarty.const.PAGE_TYPE_LINK}
        <div class="control-group">
            <label class="control-label" for="elm_page_descr">{__("description")}:</label>
            <div class="controls">
                <textarea id="elm_page_descr" name="page_data[description]" cols="55" rows="8" class="cm-wysiwyg input-large">{$page_data.description}</textarea>

                {if $view_uri}
                    {include
                        file="buttons/button.tpl"
                        but_href="customization.update_mode?type=live_editor&status=enable&frontend_url={$view_uri|urlencode}{if "ULTIMATE"|fn_allowed_for}&switch_company_id={$page_data.company_id}{/if}"
                        but_text=__("edit_content_on_site")
                        but_role="action"
                        but_meta="btn-small btn-live-edit cm-post"
                        but_target="_blank"}
                {/if}
            </div>
        </div>
        {/if}

        {if $page_type == $smarty.const.PAGE_TYPE_LINK}
            {include file="views/pages/components/pages_link.tpl"}
        {/if}

        {/hook}

        {include file="common/select_status.tpl" input_name="page_data[status]" id="elm_page_status" obj=$page_data hidden=true}

    </fieldset>
    </div>

    {if $page_type != $smarty.const.PAGE_TYPE_LINK}
    {include file="common/subheader.tpl" title=__("seo_meta_data") target="#pages_seo_meta_data_setting"}
    <div id="pages_seo_meta_data_setting" class="in collapse">
        <fieldset>

            <div class="control-group">
                <label class="control-label" for="elm_page_title">{__("page_title")}:</label>
                <div class="controls">
                    <input type="text" name="page_data[page_title]" id="elm_page_title" size="55" value="{$page_data.page_title}" class="input-large" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_page_meta_descr">{__("meta_description")}:</label>
                <div class="controls">
                    <textarea name="page_data[meta_description]" id="elm_page_meta_descr" cols="55" rows="2" class="input-large">{$page_data.meta_description}</textarea>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_page_meta_keywords">{__("meta_keywords")}:</label>
                <div class="controls">
                    <textarea name="page_data[meta_keywords]" id="elm_page_meta_keywords" cols="55" rows="2" class="input-large">{$page_data.meta_keywords}</textarea>
                </div>
            </div>

        </fieldset>
    </div>
    {/if}

    {include file="common/subheader.tpl" title=__("availability") target="#pages_availability_setting"}

  <div id="pages_availability_setting" class="in collapse">
      <fieldset>
          {if !"ULTIMATE:FREE"|fn_allowed_for}
              <div class="control-group">
                  <label class="control-label">{__("usergroups")}:</label>
                      <div class="controls">
                          {include file="common/select_usergroups.tpl" id="ug_id" name="page_data[usergroup_ids]" usergroups=["type"=>"C", "status"=>["A", "H"]]|fn_get_usergroups:$smarty.const.DESCR_SL usergroup_ids=$page_data.usergroup_ids input_extra="" list_mode=false}
                      </div>
              </div>
          {/if}
          <div class="control-group">
              <label class="control-label" for="elm_page_date">{__("creation_date")}:</label>
              <div class="controls">
                  {include file="common/calendar.tpl" date_id="elm_page_date" date_name="page_data[timestamp]" date_val=$page_data.timestamp|default:$smarty.const.TIME start_year=$settings.Company.company_start_year}
              </div>
          </div>

          {include file="views/localizations/components/select.tpl" data_name="page_data[localization]" data_from=$page_data.localization}

          <div class="control-group">
              <label class="control-label" for="elm_page_use_avail_period">{__("use_avail_period")}:</label>
              <div class="controls">
                  <input type="hidden" name="page_data[use_avail_period]" value="N">
                    <span class="checkbox">
                        <input type="checkbox" name="page_data[use_avail_period]" id="elm_page_use_avail_period" {if $page_data.use_avail_period == "Y"}checked="checked"{/if} value="Y"  onclick="fn_activate_calendar(this);">
                    </span>
              </div>
          </div>

          {capture name="calendar_disable"}{if $page_data.use_avail_period != "Y"}disabled="disabled"{/if}{/capture}

          <div class="control-group">
              <label class="control-label" for="elm_page_avail_from">{__("avail_from")}:</label>
              <div class="controls">
                  {include file="common/calendar.tpl" date_id="elm_page_avail_from" date_name="page_data[avail_from_timestamp]" date_val=$page_data.avail_from_timestamp|default:$smarty.const.TIME start_year=$settings.Company.company_start_year extra=$smarty.capture.calendar_disable}
              </div>
          </div>

          <div class="control-group">
              <label class="control-label" for="elm_page_avail_till">{__("avail_till")}:</label>
              <div class="controls">
                  {include file="common/calendar.tpl" date_id="elm_page_avail_till" date_name="page_data[avail_till_timestamp]" date_val=$page_data.avail_till_timestamp|default:$smarty.const.TIME start_year=$settings.Company.company_start_year extra=$smarty.capture.calendar_disable}
              </div>
          </div>
    </fieldset>
  </div>
        {literal}
            <script language="javascript">
                function fn_activate_calendar(el)
                {
                    Tygh.$('#elm_page_avail_from').prop('disabled', !el.checked);
                    Tygh.$('#elm_page_avail_till').prop('disabled', !el.checked);
                }
            </script>
        {/literal}

    </div>

    <div id="content_addons">
        {if $page_type != $smarty.const.PAGE_TYPE_LINK}
            {hook name="pages:detailed_content"}
            {/hook}
        {/if}
    </div>

    {hook name="pages:tabs_content"}
    {/hook}

{if !$id}
    {assign var="_title" value=__($page_type_data.new_name)}
{else}
    {assign var="_title" value="{__($page_type_data.edit_name)}: `$page_data.page`"}
    {assign var="select_languages" value=true}
    {if $view_uri}
        {capture name="preview"}
            <li>{btn type="list" target="_blank" text=__("preview") href=$view_uri}</li>
        {/capture}
    {/if}
{/if}

{capture name="buttons"}
    {if $id}
        {capture name="tools_list"}

            {foreach from=$page_types key="_k" item="_p"}
                <li>{btn type="list" text=__($_p.add_name) href="pages.add?page_type=`$_k`&parent_id=$id&come_from=$come_from"}</li>
            {/foreach}

            {hook name="pages:tools_list"}
            {/hook}
            <li class="divider"></li>
            {$smarty.capture.preview nofilter}
            {if $id}
                <li>{btn type="list" text=__("clone_this_page") href="pages.clone?page_id=$id&come_from=$come_from" method="POST"}</li>
            {/if}
            {if $allow_save}
                <li>{btn type="list" text=__("delete_this_page") class="cm-confirm" href="pages.delete?page_id=$id&come_from=$come_from" method="POST"}</li>
            {/if}
        {/capture}
    {/if}
    {dropdown content=$smarty.capture.tools_list}

    {if !$show_save_btn}
        {assign var="hide_first_button" value=true}
        {assign var="hide_second_button" value=true}
    {/if}
    {include file="buttons/save_cancel.tpl" but_name="dispatch[pages.update]" hide_first_button=$hide_first_button hide_second_button=$hide_second_button but_target_form="page_update_form" save=$id}
{/capture}

<!--update_page_form_{$page_data.page_id}--></div>
</form>

{hook name="pages:tabs_extra"}
{/hook}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox track=true}

{/capture}

{capture name="sidebar"}
    {hook name="pages:update_sidebar"}
{if $pages_tree}
    <div class="sidebar-row">
        <h6>{__("pages")}</h6>
        <div class="nested-tree">
            {include file="views/pages/components/pages_links_tree.tpl" show_all=false pages_tree=$pages_tree}
        </div>
    </div>
{/if}
    {/hook}
{/capture}

{include file="common/mainbox.tpl" title=$_title sidebar=$smarty.capture.sidebar sidebar_position="left" content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons}

{if "MULTIVENDOR"|fn_allowed_for}
  <script type="text/javascript">
    var fn_change_vendor_for_page = function(elm){
      $.ceAjax('request', Tygh.current_url, {
        data: {
          page_data: {
            company_id: $('[name="page_data[company_id]"]').val()
          }
        },
        result_ids: 'parent_page_selector'
      });
    };
  </script>
{/if}