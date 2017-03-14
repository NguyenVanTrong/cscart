{** block-description:discussion_title_home_page **}

{assign var="discussion" value=0|fn_get_discussion:"E":true:$block.properties}

{if $discussion && $discussion.type != "D" && $discussion.posts}

{foreach from=$discussion.posts item=post}
    <div class="ty-discussion-post__content">
        {hook name="discussion:items_list_row"}
        <span class="ty-discussion-post__author">{$post.name}</span>
        <span class="ty-discussion-post__date">{$post.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span>
        <div class="ty-discussion-post {cycle values=", ty-discussion-post_even"}" id="post_{$post.post_id}">
            <span class="ty-caret"> <span class="ty-caret-outer"></span> <span class="ty-caret-inner"></span></span>

            {if $discussion.type == "R" || $discussion.type == "B" && $post.rating_value > 0}
                <div class="clearfix ty-discussion-post__rating">
                    {include file="addons/discussion/views/discussion/components/stars.tpl" stars=$post.rating_value|fn_get_discussion_rating}
                </div>
            {/if}

            {if $discussion.type == "C" || $discussion.type == "B"}
                <div class="ty-discussion-post__message">{$post.message|escape|nl2br nofilter}</div>
            {/if}

        </div>
        {/hook}
    </div>
{/foreach}

<div class="ty-mtb-s ty-left ty-uppercase">
    <a href="{"discussion.view?thread_id=`$discussion.thread_id`"|fn_url}">{__("view_all")}</a>
</div>
{/if}
