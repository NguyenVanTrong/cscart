<div class="deb-tab-content" id="DebugToolbarTabBlocksContent">
    <div class="deb-sub-tab">
        <ul>
            <li{if $blocks_rendered} class="active"{/if}><a
                        data-sub-tab-id="DebugToolbarSubTabBlocksRendered">Rendered</a></li>
            <li{if !$blocks_rendered} class="active"{/if}><a data-sub-tab-id="DebugToolbarSubTabBlocksFromCache">From
                    cache</a></li>
        </ul>
    </div>

    <div class="deb-sub-tab-content" id="DebugToolbarSubTabBlocksRendered">
        {include file="backend:views/debugger/components/blocks_table.tpl" blocks=$blocks_rendered}
    </div>

    <div class="deb-sub-tab-content" id="DebugToolbarSubTabBlocksFromCache">
        {include file="backend:views/debugger/components/blocks_table.tpl" blocks=$blocks_from_cache}
    </div>
<!--DebugToolbarTabBlocksContent--></div>


