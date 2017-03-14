{** block-description:blog.recent_posts **}

{if $items}

<div class="ty-blog-sidebox">
    <ul class="ty-blog-sidebox__list">
{foreach from=$items item="page"}
        <li class="ty-blog-sidebox__item">
            <a href="{"pages.view?page_id=`$page.page_id`"|fn_url}">{$page.page}</a>
        </li>
{/foreach}
    </ul>
</div>

{/if}