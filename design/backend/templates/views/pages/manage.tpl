{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="pages_tree_form" id="pages_tree_form">
<input type="hidden" name="redirect_url" value="{$config.current_url}" />

{if $search.page_type}
<input type="hidden" name="page_type" value="{$search.page_type}" />
{/if}

{if $page_types[$search.page_type].hide_fields && $page_types[$search.page_type].hide_fields.position}
    {$hide_position = true}
{/if}

{assign var="come_from" value=$search.page_type}
{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id hide_position=$hide_position}

<div class="items-container multi-level">
    {include file="views/pages/components/pages_tree.tpl" header=true combination_suffix="_list"}
</div>

{include file="common/pagination.tpl" div_id=$smarty.request.content_id}

{assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}

{capture name="adv_buttons"}
    {if $page_types|sizeof == 1}
        {foreach from=$page_types key="_k" item="_p"}
            {include file="common/tools.tpl" tool_href="pages.add?page_type=`$_k`&come_from=`$come_from`" prefix="top" title=__($_p.add_name) hide_tools=true icon="icon-plus"}
        {/foreach}
    {else}
        {capture name="tools_list"}
            {foreach from=$page_types key="_k" item="_p"}
                <li>{btn type="list" text=__($_p.add_name) href="pages.add?page_type=`$_k`&come_from=`$come_from`"}</li>
            {/foreach}
        {/capture}
        {dropdown content=$smarty.capture.tools_list icon="icon-plus" no_caret=true placement="right"}
    {/if}
{/capture}

{capture name="buttons"}
    {if $pages_tree}
        {capture name="tools_list"}
            <li>{btn type="list" text=__("clone_selected") dispatch="dispatch[pages.m_clone]" form="pages_tree_form"}</li>
            <li>{btn type="delete_selected" dispatch="dispatch[pages.m_delete]" form="pages_tree_form"}</li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
        {include file="buttons/save.tpl" but_name="dispatch[pages.m_update]" but_role="submit-button" but_target_form="pages_tree_form"}
    {/if}
{/capture}
</form>
{/capture}

{if $is_exclusive_page_type}
    {$title = __($page_types[$search.page_type].content)}
    {$view_type = "pages_`$search.page_type`"}
    {$view_suffix = "page_type=`$search.page_type`&get_tree=multi_level"}
{else}
    {$title = __("pages")}
    {$view_type = "pages"}
    {$view_suffix = ""}
{/if}

{capture name="sidebar"}
    {hook name="pages:manage_sidebar"}
    {include file="common/saved_search.tpl" dispatch="pages.manage" view_type=$view_type view_suffix=$view_suffix}
    {include file="views/pages/components/pages_search_form.tpl" dispatch="pages.manage" view_type=$view_type}
    {hook name="pages:sidebar"}
    {/hook}
    {/hook}
{/capture}


{include file="common/mainbox.tpl" title=$title content=$smarty.capture.mainbox  buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar content_id="manage_pages"}