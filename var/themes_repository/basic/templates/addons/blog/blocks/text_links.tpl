{** block-description:blog.text_links **}

{if $items}
<div class="ty-blog-text-links">
    <ul>
    {foreach from=$items item="page" name="fe_blog"}
        <li class="ty-blog-text-links__item">
            <div class="ty-blog-text-links__date">{$page.timestamp|date_format:$settings.Appearance.date_format}</div>
            <a href="{"pages.view?page_id=`$page.page_id`"|fn_url}" class="ty-blog-text-links__a">{$page.page}</a>
            {if $smarty.foreach.fe_blog.last}
                {$parent_id = $page.parent_id}
            {/if}
        </li>
    {/foreach}
    </ul>

    <div class="ty-mtb-xs ty-left">
        {include file="buttons/button.tpl" but_href="pages.view?page_id=`$parent_id`" but_text=__("view_all") but_role="text" but_meta="ty-btn__secondary blog-ty-text-links__button"}
    </div>
</div>
{/if}