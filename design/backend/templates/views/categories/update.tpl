{if $category_data.category_id}
    {assign var="id" value=$category_data.category_id}
    {assign var="is_trash" value=$category_data.is_trash == 'Y'}
{else}
    {assign var="id" value=0}
{/if}

{if $id}
    {$view_uri = "categories.view?category_id=`$id`"|fn_get_preview_url:$category_data:$auth.user_id}
{/if}

{capture name="mainbox"}

{capture name="tabsbox"}

<form action="{""|fn_url}" method="post" name="category_update_form" class="form-horizontal form-edit {if ""|fn_check_form_permissions} cm-hide-inputs{/if}" enctype="multipart/form-data">
<input type="hidden" name="fake" value="1" />
<input type="hidden" name="category_id" value="{$id}" />
<input type="hidden" name="selected_section" value="{$smarty.request.selected_section}" />

<div id="content_detailed">

    {include file="common/subheader.tpl" title=__("information") target="#acc_information"}
    <div id="acc_information" class="collapsed in">
    <div class="control-group">
        <label for="elm_category_name" class="control-label cm-required">{__("name")}:</label>
        <div class="controls">
            <input type="text" name="category_data[category]" id="elm_category_name" size="55" value="{$category_data.category}" class="input-large" {if $is_trash}readonly="readonly"{/if} />
        </div>
    </div>
    <div class="control-group">
        {if "categories"|fn_show_picker:$smarty.const.CATEGORY_THRESHOLD}
            <label class="control-label cm-required" for="elm_category_parent_id">{__("location")}:</label>
            <div class="controls">
                {include file="pickers/categories/picker.tpl" data_id="location_category" input_name="category_data[parent_id]" item_ids=$category_data.parent_id|default:"0" hide_link=true hide_delete_button=true default_name=__("root_level") display_input_id="elm_category_parent_id" except_id=$id}
            </div>
        {else}
            <label class="control-label" for="elm_category_parent_id">{__("location")}:</label>

            <div class="controls">
            <select name="category_data[parent_id]" id="elm_category_parent_id">
                <option value="0" {if $category_data.parent_id == "0"}selected="selected"{/if}>- {__("root_level")} -</option>
                {foreach from=0|fn_get_plain_categories_tree:false item="cat" name="categories"}
                {if !"ULTIMATE"|fn_allowed_for}
                    {if $cat.id_path|strpos:"`$category_data.id_path`/" === false && $cat.category_id != $id || !$id}
                        <option value="{$cat.category_id}" {if $cat.disabled}disabled="disabled"{/if} {if $category_data.parent_id == $cat.category_id}selected="selected"{/if}>{$cat.category|escape|indent:$cat.level:"&#166;&nbsp;&nbsp;&nbsp;&nbsp;":"&#166;--&nbsp;" nofilter}</option>
                    {/if}
                {/if}
                {if "ULTIMATE"|fn_allowed_for}
                    {if $cat.store}
                        {if !$smarty.foreach.categories.first}
                            </optgroup>
                        {/if}
                        <optgroup label="{$cat.category}">
                    {else}
                        {if $cat.id_path|strpos:"`$category_data.id_path`/" === false && $cat.category_id != $id || !$id}
                            <option value="{$cat.category_id}" {if $cat.disabled}disabled="disabled"{/if} {if $category_data.parent_id == $cat.category_id}selected="selected"{/if}>{$cat.category|escape|indent:$cat.level:"&#166;&nbsp;&nbsp;&nbsp;&nbsp;":"&#166;--&nbsp;" nofilter}</option>
                        {/if}
                    {/if}
                {/if}
                {/foreach}
            </select>
            </div>
        {/if}
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_category_descr">{__("description")}:</label>
        <div class="controls">
            <textarea id="elm_category_descr" name="category_data[description]" cols="55" rows="8" class="input-large cm-wysiwyg input-textarea-long">{$category_data.description}</textarea>
            {if $id}
                {include
                    file="buttons/button.tpl"
                    but_href="customization.update_mode?type=live_editor&status=enable&frontend_url={$view_uri|urlencode}{if "ULTIMATE"|fn_allowed_for}&switch_company_id={$category_data.company_id}{/if}"
                    but_text=__("edit_content_on_site")
                    but_role="action"
                    but_meta="btn-small btn-live-edit cm-post"
                    but_target="_blank"}
            {/if}
        </div>
    </div>

    {include file="common/select_status.tpl" input_name="category_data[status]" id="elm_category_status" obj=$category_data hidden=true}

    {if "ULTIMATE"|fn_allowed_for}
    {include file="views/companies/components/company_field.tpl"
        name="category_data[company_id]"
        id="category_data_company_id"
        selected=$category_data.company_id
    }
    {/if}

    <div class="control-group">
        <label class="control-label">{__("images")}:</label>
        <div class="controls">
            {include file="common/attach_images.tpl" image_name="category_main" image_object_type="category" image_pair=$category_data.main_pair image_object_id=$id icon_text=__("text_category_icon") detailed_text=__("text_category_detailed_image") no_thumbnail=true}
        </div>
    </div>

    </div>
    <hr />

    {include file="common/subheader.tpl" title=__("seo_meta_data") target="#acc_seo"}

    <div id="acc_seo" class="collapsed in">
    <div class="control-group">
        <label class="control-label" for="elm_category_page_title">{__("page_title")}:</label>
        <div class="controls">
            <input type="text" name="category_data[page_title]" id="elm_category_page_title" size="55" value="{$category_data.page_title}" class="input-large" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_category_meta_description">{__("meta_description")}:</label>
        <div class="controls">
            <textarea name="category_data[meta_description]" id="elm_category_meta_description" cols="55" rows="4" class="input-large">{$category_data.meta_description}</textarea>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_category_meta_keywords">{__("meta_keywords")}:</label>
        <div class="controls">
            <textarea name="category_data[meta_keywords]" id="elm_category_meta_keywords" cols="55" rows="4" class="input-large">{$category_data.meta_keywords}</textarea>
        </div>
    </div>
    </div>
    <hr />
    {if !"ULTIMATE:FREE"|fn_allowed_for}
    {include file="common/subheader.tpl" title=__("availability") target="#acc_availability"}
    <div id="acc_availability">
    <div class="control-group">
        <label class="control-label">{__("usergroups")}:</label>
            <div class="controls">
                {include file="common/select_usergroups.tpl" id="ug_id" name="category_data[usergroup_ids]" usergroups=["type"=>"C", "status"=>["A", "H"]]|fn_get_usergroups:$smarty.const.DESCR_SL usergroup_ids=$category_data.usergroup_ids input_extra="" list_mode=false}
                <label class="checkbox" for="usergroup_to_subcats">{__("to_all_subcats")}
                    <input id="usergroup_to_subcats" type="checkbox" name="category_data[usergroup_to_subcats]" value="Y" />
                </label>
            </div>
    </div>
    {/if}

    <div class="control-group">
        <label class="control-label" for="elm_category_position">{__("position")}:</label>
        <div class="controls">
            <input type="text" name="category_data[position]" id="elm_category_position" size="10" value="{$category_data.position}" class="input-text-short" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_category_creation_date">{__("creation_date")}:</label>
        <div class="controls">
            {include file="common/calendar.tpl" date_id="elm_category_creation_date" date_name="category_data[timestamp]" date_val=$category_data.timestamp|default:$smarty.const.TIME start_year=$settings.Company.company_start_year}
        </div>
    </div>

    {include file="views/localizations/components/select.tpl" data_from=$category_data.localization data_name="category_data[localization]"}
</div>
</div>

<div id="content_views">
    <div id="extra">
        <div class="control-group">
            <label class="control-label" for="elm_category_product_layout">{__("product_details_view")}:</label>
            <div class="controls">
            <select id="elm_category_product_layout" name="category_data[product_details_view]">
                {foreach from="category"|fn_get_product_details_views key="layout" item="item"}
                    <option {if $category_data.product_details_view == $layout}selected="selected"{/if} value="{$layout}">{$item}</option>
                {/foreach}
            </select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_category_use_custom_templates">{__("use_custom_view")}:</label>
            <div class="controls">
            <input type="hidden" value="N" name="category_data[use_custom_templates]"/>
            <input type="checkbox" class="cm-toggle-checkbox" value="Y" name="category_data[use_custom_templates]" id="elm_category_use_custom_templates"{if $category_data.selected_views} checked="checked"{/if} />
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_category_product_columns">{__("product_columns")}:</label>
            <div class="controls">
            <input type="text" name="category_data[product_columns]" id="elm_category_product_columns" size="10" value="{$category_data.product_columns}" class="cm-toggle-element" {if !$category_data.selected_views}disabled="disabled"{/if} />
            </div>
        </div>

        {assign var="layouts" value=""|fn_get_products_views:false:false}
        <div class="control-group">
            <label class="control-label">{__("available_views")}:</label>
            <div class="controls">
                {foreach from=$layouts key="layout" item="item"}
                    <label class="checkbox" for="elm_category_layout_{$layout}"><input type="checkbox" class="cm-combo-checkbox cm-toggle-element" name="category_data[selected_views][{$layout}]" id="elm_category_layout_{$layout}" value="{$layout}" {if ($category_data.selected_views.$layout) || (!$category_data.selected_views && $item.active)}checked="checked"{/if} {if !$category_data.selected_views}disabled="disabled"{/if} />{$item.title}</label>
                {/foreach}
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_category_default_view">{__("default_category_view")}:</label>
            <div class="controls">
            <select id="elm_category_default_view" class="cm-combo-select cm-toggle-element" name="category_data[default_view]" {if !$category_data.selected_views}disabled="disabled"{/if}>
                {foreach from=$layouts key="layout" item="item"}
                    {if ($category_data.selected_views.$layout) || (!$category_data.selected_views && $item.active)}
                        <option {if $category_data.default_view == $layout}selected="selected"{/if} value="{$layout}">{$item.title}</option>
                    {/if}
                {/foreach}
            </select>
            </div>
        </div>
    </div>
</div>

<div id="content_addons">
{hook name="categories:detailed_content"}
{/hook}
</div>

{hook name="categories:tabs_content"}
{/hook}

{capture name="buttons"}
    {if $id}
        {include file="common/view_tools.tpl" url="categories.update?category_id="}

        {capture name="tools_list"}
            {hook name="categories:update_tools_list"}
                <li>{btn type="list" href="categories.add?parent_id=$id" text=__("add_subcategory")}</li>
                <li>{btn type="list" href="products.add?category_id=$id" text=__("add_product")}</li>
                <li>{btn type="list" target="_blank" text=__("preview") href=$view_uri}</li>
                <li class="divider"></li>
                <li>{btn type="list" href="products.manage?cid=$id" text=__("view_products")}</li>
                <li>{btn type="list" class="cm-confirm" text=__("delete_this_category") data=["data-ca-confirm-text" => "{__("category_deletion_side_effects")}"] href="categories.delete?category_id=`$id`" method="POST"}</li>
            {/hook}
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
    {include file="buttons/save_cancel.tpl" but_role="submit-link" but_target_form="category_update_form" but_name="dispatch[categories.update]" save=$id}
{/capture}
</form>

{if $id}
    {hook name="categories:tabs_extra"}
    {/hook}
{/if}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox group_name=$runtime.controller active_tab=$smarty.request.selected_section track=true}

{/capture}

{capture name="sidebar"}
    {hook name="categories:update_sidebar"}
{if $categories_tree}
    <div class="sidebar-row">
        <h6>{__("categories")}</h6>
        <div class="nested-tree">
            {include file="views/categories/components/categories_links_tree.tpl" show_all=false categories_tree=$categories_tree}
        </div>
    </div>
{/if}
    {/hook}
{/capture}

{if !$id}
    {include file="common/mainbox.tpl" title=__("new_category") sidebar=$smarty.capture.sidebar sidebar_position="left" content=$smarty.capture.mainbox buttons=$smarty.capture.buttons}
{else}
    {include file="common/mainbox.tpl" sidebar=$smarty.capture.sidebar sidebar_position="left" title="{__("editing_category")}: `$category_data.category`" content=$smarty.capture.mainbox select_languages=true buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons}
{/if}


