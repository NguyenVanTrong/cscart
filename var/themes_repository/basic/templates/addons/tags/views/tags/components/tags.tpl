{if $object.tags}
<div id="content_tags_tab">
    <div class="control-group">
        <ul class="tag-product clearfix">
            {foreach from=$object.tags item="tag" name="tags"}
            {assign var="tag_name" value=$tag.tag|escape:url}
                <li>
                    <a href="{"tags.view?tag=`$tag_name`"|fn_url}">
                        {$tag.tag}                                    
                    </a>
                </li>
            {/foreach}
        </ul>
    </div>  
</div>
{/if}