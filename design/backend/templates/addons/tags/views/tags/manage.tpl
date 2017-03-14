{capture name="mainbox"}

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

<form class="form-horizontal form-edit" action="{""|fn_url}" method="post" name="tags_form">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{if $tags}
<table width="100%" class="table table-sort table-middle">
<thead>
<tr>
    <th class="left" width="1%">{include file="common/check_items.tpl"}</th>
    <th width="50%"><a class="cm-ajax{if $search.sort_by == "tag"} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=tag&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("tag")}</a></th>
    {foreach from=$tag_objects item="o"}
    <th class="center">&nbsp;&nbsp;{__($o.name)}&nbsp;&nbsp;</th>
    {/foreach}
    <th>&nbsp;</th>
    <th class="right" width="12%"><a class="cm-ajax{if $search.sort_by == "status"} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("status")}</a></th>
</tr>
</thead>
{foreach from=$tags item="tag"}
<tbody>
    <tr>
        <td class="left"><input type="checkbox" class="cm-item" value="{$tag.tag_id}" name="tag_ids[]"/></td>
        <td>
            <input type="text" name="tags_data[{$tag.tag_id}][tag]" value="{$tag.tag}" size="20" class="input-hidden">
        </td>
        {foreach from=$tag_objects key="k" item="o"}
        <td class="center">
            {if $tag.objects_count.$k}<a href="{$o.url|fn_link_attach:"tag=`$tag.tag`"|fn_url}">{$tag.objects_count.$k}</a>{else}0{/if}
        </td>
        {/foreach}
        <td>
            <div class="hidden-tools">
                {capture name="tools_list"}
                    <li>{btn type="list" class="cm-confirm" text=__("delete") href="tags.delete?tag_id=`$tag.tag_id`" method="POST"}</li>
                {/capture}
                {dropdown content=$smarty.capture.tools_list}
            </div>
        </td>
        <td class="right">
            {include file="common/select_popup.tpl" id=$tag.tag_id status=$tag.status items_status="tags"|fn_get_predefined_statuses object_id_name="tag_id" table="tags"}
        </td>
    </tr>
{/foreach}
</tbody>
</table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

</form>


{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        {if $tags}
            {hook name="tags:list_extra_links"}
                <li>{btn type="list" text=__("activate_selected") dispatch="dispatch[tags.approve]" form="tags_form"}</li>
                <li>{btn type="list" text=__("disable_selected") dispatch="dispatch[tags.disapprove]" form="tags_form"}</li>
            {/hook}
            <li class="divider"></li>
            <li>{btn type="delete_selected" dispatch="dispatch[tags.m_delete]" form="tags_form"}</li>
        {/if}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
    {if $tags}
        {include file="buttons/save.tpl" but_name="dispatch[tags.m_update]" but_role="submit-link" but_target_form="tags_form"}
    {/if}
{/capture}

{capture name="sidebar"}
    {include file="common/saved_search.tpl" dispatch="tags.manage" view_type="tags"}
    {include file="addons/tags/views/tags/components/tags_search_form.tpl" dispatch="tags.manage"}
{/capture}

{include file="common/mainbox.tpl" title=__("tags") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar}