{if $page_type == $smarty.const.PAGE_TYPE_BLOG}

{include file="common/subheader.tpl" title=__("blog") target="#blog_image"}
<div id="blog_image" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label">{__("image")}:</label>
            <div class="controls">
                {include file="common/attach_images.tpl" image_name="blog_image" image_object_type="blog" image_pair=$page_data.main_pair no_detailed=true hide_titles=true}
            </div>
        </div>
    </fieldset>
</div>

{/if}
