{if fn_check_permissions("snippets", "update", "admin", "POST")}
    {assign var="return_url_escape" value=$return_url|escape:"url"}

    <div class="cm-tab-tools hidden" id="tools_snippets">

        {include file="common/popupbox.tpl"
            method="POST"
            id="add_snippet"
            text="{__("add_snippet")}"
            title=__("add_snippet")
            act="general"
            icon="icon-plus"
            href="snippets.update?snippet_id=0&return_url={$return_url_escape}&current_result_ids={$result_ids}&type={$type}&addon={$addon}"
        }
    </div>
{/if}