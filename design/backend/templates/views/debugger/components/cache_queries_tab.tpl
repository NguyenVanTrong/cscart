<div class="deb-tab-content" id="DebugToolbarTabCacheQueriesContent">
    {capture name="cache_queries_tabs"}
    <div class="deb-sub-tab-content" id="DebugToolbarSubTabCacheQueriesList">
        {capture name="cache_queries_list_table"}
        <table class="deb-table" id="DebugToolbarSubTabCacheQueriesListTable">
            <caption>Queries <small class="deb-font-gray">time: {$data.totals.time|number_format:"5"}</small></caption>
            <tr>
                <th style="width: 35px;">{include file="backend:views/debugger/components/sorter.tpl" text="№"    field="number" order_by=$order_by direction=$direction url="debugger.cache_queries" debugger_hash=$debugger_hash target_id="DebugToolbarTabCacheQueriesContent"}</th>
                <th>Query</th>
                <th style="width: 60px;">{include file="backend:views/debugger/components/sorter.tpl" text="Time" field="time"   order_by=$order_by direction=$direction url="debugger.cache_queries" debugger_hash=$debugger_hash target_id="DebugToolbarTabCacheQueriesContent"}</th>
            </tr>

            {foreach from=$data.list item="query" key="key"}
                {if $query.time > $long_query_time}
                    {assign var="color" value="deb-light-red"}
                {elseif $query.time > $medium_query_time}
                    {assign var="color" value="deb-light2-red"}
                {else}
                    {assign var="color" value=false}
                {/if}
                <tr>
                    <td {if $color}class="{$color}"{/if}><strong>{$key+1}</strong></td>
                    <td class="sql {if $color}{$color}{/if}"><pre><code>{$query.query}</code></pre></td>
                    <td {if $color}class="{$color}"{/if}><strong>{$query.time|number_format:"5"}</strong></td>
                </tr>

            {/foreach}
        </table>
        {/capture}
        {$smarty.capture.cache_queries_list_table nofilter}
    </div>
    {/capture}

    <div class="deb-sub-tab">
        <ul>
            <li class="active"><a data-sub-tab-id="DebugToolbarSubTabCacheQueriesList">Queries list</a></li>
        </ul>
    </div>
    {$smarty.capture.cache_queries_tabs nofilter}
    <!--DebugToolbarTabCacheQueriesContent--></div>
