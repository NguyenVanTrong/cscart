<div id="install_notices_{$id}">
    {if !$validation_result && $validation_data}
        <div class="upgrade-center_adv-content" >
            {hook name="upgrade_center:validators"}
                {if $validation_data.permissions || $validation_data.restore}
                    {include file="views/upgrade_center/components/permissions.tpl" data=$validation_data.permissions|default:$validation_data.restore id=$id type=$type}

                {elseif $validation_data.collisions}
                    {include file="views/upgrade_center/components/collisions.tpl" data=$validation_data.collisions id=$id type=$type}
                {else}
                    {foreach $validation_data as $validator_name => $data}
                        {include file="views/upgrade_center/components/general.tpl" validator_name=$validator_name data=$data id=$id type=$type}
                    {/foreach}
                {/if}
            {/hook}
        </div>
    {/if}
<!--install_notices_{$id}--></div>