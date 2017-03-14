{script src="js/tygh/tabs.js"}

{if !$active_tab}
    {assign var="active_tab" value=$smarty.request.selected_section}
{/if}

{assign var="empty_tab_ids" value=$content|empty_tabs}

{if $navigation.tabs}

{$with_conf = false}
{capture name="tab_items"}
    {foreach from=$navigation.tabs item=tab key=key name=tabs}
        {if (!$tabs_section || $tabs_section == $tab.section) && ($tab.hidden || !$key|in_array:$empty_tab_ids)}
        <li id="{$key}{$id_suffix}" class="{if $tab.hidden == "Y"}hidden {/if}{if $tab.js}cm-js{elseif $tab.ajax}cm-js cm-ajax{/if}{if $key == $active_tab} active{/if} {if $tab.properties}extra-tab{/if}">
            {if $key == $active_tab}{$active_tab_extra nofilter}{/if}

            {if $tab.properties}
                {$with_conf = true}
                {btn type="dialog" class="cm-ajax-force hand icon-cog" title=$tab.properties.title target_id="content_properties_`$key``$id_suffix`" href=$tab.properties.href}
            {/if}

            <a {if $tab.href}href="{$tab.href|fn_url}"{/if}>{$tab.title}</a>
        </li>
        {/if}
    {/foreach}
{/capture}

<div class="cm-j-tabs{if $track} cm-track{/if} tabs {if $with_conf}tabs-with-conf{/if}">
    <ul class="nav nav-tabs">
        {$smarty.capture.tab_items nofilter}
    </ul>
</div>
<div class="cm-tabs-content">
    {$content nofilter}
</div>
{else}
    {$content nofilter}
{/if}