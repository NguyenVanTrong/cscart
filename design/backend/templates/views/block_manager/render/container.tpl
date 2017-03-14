{if $container.default != 1 && !$dynamic_object && $container.position|in_array:["TOP_PANEL", "HEADER", "FOOTER"] && $container.linked_to_default == "Y"}
    {$linked = true}
{else}
    {$linked = false}
{/if}

<div id="container_{$container.container_id}" class="container container_{$container.width} {if $linked}container-lock{/if} {if $container.status == "D"}container-off{/if}" {if $container.status != "A"}data-ca-status="disabled"{else}data-ca-status="active"{/if}>
    {if $linked}<p>{__("container_not_used", ["[container]" => __($container.position)])} <a class="cm-post" href="{"block_manager.set_custom_container?container_id=`$container.container_id`&linked_to_default=N&selected_location=`$location.location_id`"|fn_url}">{__("set_custom_configuration")}</a></p>{/if}

    {if $container.default == 1 || $container.position == 'CONTENT' || $dynamic_object || $container.linked_to_default != "Y"}
        {$content nofilter}
    {/if}
    
    <div class="clearfix"></div>
    <div class="grid-control-menu bm-control-menu">
        {if $container.default == 1 || $container.position == 'CONTENT' && !$dynamic_object || $container.linked_to_default != "Y"}
            <div class="grid-control-menu-actions">
                <div class="btn-group action">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="icon-plus cm-tooltip" data-ce-tooltip-position="top" title="{__("insert_grid")}"></span></a>
                    <ul class="dropdown-menu droptop">
                        <li><a href="#" class="cm-action bm-action-add-grid">{__("insert_grid")}</a></li>
                    </ul>
                </div>
                <div class="cm-tooltip cm-action icon-cog bm-action-properties action" data-ce-tooltip-position="top" title="{__("container_options")}"></div>
                <div class="cm-action bm-action-switch cm-tooltip icon-off action" data-ce-tooltip-position="top" title="{__("enable_or_disable_container")}"></div>
            </div>
        {/if}

        <h4 class="grid-control-title">{__($container.position)}
            {if $container.default != 1 && !$dynamic_object && $container.position|in_array:["TOP_PANEL", "HEADER", "FOOTER"]}
                <a class="cm-post" href="{"block_manager.set_custom_container?container_id=`$container.container_id`&linked_to_default=Y&selected_location=`$location.location_id`"|fn_url}">{__("use_default_block_configuration")}</a>
            {/if}
        </h4>
    </div>
<!--container_{$container.container_id}--></div>

<hr />