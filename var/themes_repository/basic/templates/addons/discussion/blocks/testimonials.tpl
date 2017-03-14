{** block-description:discussion_title_home_page **}

{assign var="discussion" value=0|fn_get_discussion:"E":true:$block.properties}

{if $discussion && $discussion.type != "D" && $discussion.posts}

{assign var="obj_prefix" value="`$block.block_id`000"}

<div class="ty-mb-l">
    <div class="ty-scroller-discussion-list">
        <div id="scroll_list_{$block.block_id}" class="owl-carousel ty-scroller-list">

        {foreach from=$discussion.posts item=post}
            <div class="ty-discussion-post__content ty-scroller-discussion-list__item">
                {hook name="discussion:items_list_row"}
                <a href="{"discussion.view?thread_id=`$discussion.thread_id`&post_id=`$post.post_id`"|fn_url}#post_{$post.post_id}">
                    <div class="ty-discussion-post {cycle values=", ty-discussion-post_even"}" id="post_{$post.post_id}">

                        {if $discussion.type == "C" || $discussion.type == "B"}
                        <div class="ty-discussion-post__message">{$post.message|truncate:100|nl2br nofilter}</div>
                        {/if}

                        <span class="ty-caret-bottom"><span class="ty-caret-outer"></span><span class="ty-caret-inner"></span></span>
                    
                    </div>
                </a>

                <span class="ty-discussion-post__author">{$post.name}</span>
                <span class="ty-discussion-post__date">{$post.timestamp|date_format:"`$settings.Appearance.date_format`"}</span>
                {if $discussion.type == "R" || $discussion.type == "B" && $post.rating_value > 0}
                    <div class="clearfix ty-discussion-post__rating">
                        {include file="addons/discussion/views/discussion/components/stars.tpl" stars=$post.rating_value|fn_get_discussion_rating}
                    </div>
                {/if}
                {/hook}
            </div>
        {/foreach}

        </div>
    </div>
</div>

{include file="common/scroller_init.tpl"}

{/if}
