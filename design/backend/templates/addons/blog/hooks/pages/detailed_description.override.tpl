        {if $page_type != $smarty.const.PAGE_TYPE_LINK}
        <div class="control-group">
            {if $page_type == $smarty.const.PAGE_TYPE_BLOG}
            <label class="control-label" for="elm_page_descr">{__("post_description")}:</label>
            {else}
            <label class="control-label" for="elm_page_descr">{__("description")}:</label>
            {/if}
            <div class="controls">
                <textarea id="elm_page_descr" name="page_data[description]" cols="55" rows="8" class="cm-wysiwyg input-large">{$page_data.description}</textarea>
            </div>
        </div>
        {/if}

        {if $page_type == $smarty.const.PAGE_TYPE_LINK}
            {include file="views/pages/components/pages_link.tpl"}
        {/if}
