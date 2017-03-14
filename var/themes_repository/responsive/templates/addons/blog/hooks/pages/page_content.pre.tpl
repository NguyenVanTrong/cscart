{if $page.description && $page.page_type == $smarty.const.PAGE_TYPE_BLOG}
    <div class="ty-blog__date">{$page.timestamp|date_format:"`$settings.Appearance.date_format`"}</div>
    <div class="ty-blog__author">{__("by")} {$page.author}</div>
    {if $page.main_pair}
        <div class="ty-blog__img-block">
            {include file="common/image.tpl" obj_id=$page.page_id images=$page.main_pair}
        </div>
    {/if}
{/if}
