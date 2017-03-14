{** block-description:text_links **}

{if $items}
    {strip}
        <ul class="ty-text-links">
            {foreach from=$items item="page"}
                <li class="ty-text-links__item ty-level-{$page.level|default:0}{if $page.active || $page|fn_check_is_active_menu_item:$block.type} ty-text-links__active{/if}">
                    {if $page.page_type == $smarty.const.PAGE_TYPE_LINK}
                        {assign var="href" value=$page.link|fn_url}
                    {else}
                        {assign var="href" value="pages.view?page_id=`$page.page_id`"|fn_url}
                    {/if}
                    {capture name="attributes"}
                        {if $page.new_window}
                            class="ty-text-links__a"
                            target="_blank"
                        {/if}
                    {/capture}
                    <a href="{$href}" {$smarty.capture.attributes nofilter}>
                        {$page.page}
                    </a>
                </li>
            {/foreach}
        </ul>
    {/strip}
{/if}
