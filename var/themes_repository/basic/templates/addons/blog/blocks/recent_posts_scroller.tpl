{** block-description:blog.recent_posts_scroller **}

{if $items}

{assign var="obj_prefix" value="`$block.block_id`000"}

<div class="ty-mb-l">
    <div class="ty-blog-recent-posts-scroller">
        <div id="scroll_list_{$block.block_id}" class="owl-carousel ty-scroller-list">

        {foreach from=$items item="page"}
            <div class="ty-blog-recent-posts-scroller__item">

                <div class="ty-blog-recent-posts-scroller__img-block">
                    <a href="{"pages.view?page_id=`$page.page_id`"|fn_url}">
                        {include file="common/image.tpl" image_width="345" obj_id=$page.page_id images=$page.main_pair}
                    </a>
                </div>

                <a href="{"pages.view?page_id=`$page.page_id`"|fn_url}">{$page.page}</a>

                <div class="ty-blog__date">{$page.timestamp|date_format:"`$settings.Appearance.date_format`"}</div>

            </div>
        {/foreach}

        </div>
    </div>
</div>

{include file="common/scroller_init.tpl"}

{/if}