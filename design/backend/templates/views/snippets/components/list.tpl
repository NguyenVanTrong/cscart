{assign var="return_url_escape" value=$return_url|escape:"url"}
{assign var="can_update" value=fn_check_permissions('snippets', 'update', 'admin', 'POST')}
{assign var="edit_link_text" value=__("edit")}

{if !$can_update}
    {assign var="edit_link_text" value=__("view")}
{/if}

<div id="snippet_list">
    <form action="{""|fn_url}" method="post" name="snippets_form" class="form-horizontal">
        <input type="hidden" name="return_url" value="{$return_url}" />
        <input type="hidden" name="result_ids" value="{$result_ids}" />

        {if $snippets}
            <table class="table table-middle" width="100%">
                <thead>
                    <tr>
                        {if $can_update}
                            <th width="1%" class="center">
                                {include file="common/check_items.tpl"}
                            </th>
                        {/if}
                        <th width="40%">
                            {__("name")}
                        </th>
                        <th width="20%">
                            {__("code")}
                        </th>
                        {if $can_update}
                            <th class="right">&nbsp;</th>
                            <th width="10%" class="right">
                                {__("status")}
                            </th>
                        {/if}
                    </tr>
                </thead>
                <tbody>
                {foreach from=$snippets item="snippet"}
                    {$snippet_result_ids = "`$result_ids`,snippet_content_`$snippet->getId()`_*"}

                    <tr class="cm-row-status-{$snippet->getStatus()|lower} row-snippet" data-snippet-id="{$snippet->getId()}">
                        {if $can_update}
                            <td class="center">
                                <input type="checkbox" name="snippet_ids[]" value="{$snippet->getId()}" class="checkbox cm-item" />
                            </td>
                        {/if}
                        <td class="row-status">
                            <a class="cm-external-click" data-ca-target-id="{$result_ids}" data-ca-external-click-id="{"opener_snippet_`$snippet->getId()`"}">{$snippet->getName()}</a>
                        </td>
                        <td class="row-status">
                            <a class="cm-external-click" data-ca-target-id="{$result_ids}" data-ca-external-click-id="{"opener_snippet_`$snippet->getId()`"}">{$snippet->getCode()}</a>
                        </td>
                        <td class="right nowrap">
                            {capture name="tools_list"}
                                <li>
                                    {include file="common/popupbox.tpl"
                                        id="snippet_`$snippet->getId()`"
                                        text="{__("editing_snippet")}: {$snippet->getName()}"
                                        link_text=$edit_link_text
                                        act="link"
                                        href="snippets.update?snippet_id={$snippet->getId()}&return_url={$return_url_escape}&current_result_ids={$snippet_result_ids}"
                                    }
                                </li>
                                <li>
                                    {btn
                                        type="list"
                                        text=__("delete")
                                        method="post"
                                        class="cm-confirm cm-ajax"
                                        href="snippets.delete?snippet_ids={$snippet->getId()}&return_url={$return_url_escape}&result_ids={$snippet_result_ids}"
                                        data=["data-ca-target-id" => $result_ids]
                                    }
                                </li>
                            {/capture}
                            <div class="hidden-tools">
                                {dropdown content=$smarty.capture.tools_list}
                            </div>
                        </td>
                        {if $can_update}
                            <td class="right">
                                {include file="common/select_popup.tpl"
                                    id=$snippet->getId()
                                    status=$snippet->getStatus()
                                    table="template_snippets"
                                    object_id_name="snippet_id"
                                    update_controller="snippets"
                                    st_return_url=$return_url
                                    st_result_ids=$snippet_result_ids
                                }
                            </td>
                        {/if}
                    </tr>
                {/foreach}
                </tbody>
            </table>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

    </form>

<!--content_snippets--></div>