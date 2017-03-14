{foreach from=$block_types key="type" item="block"}
    <div class="select-block cm-add-block bm-action-new-block {if $manage == "Y"}bm-manage{/if}">
        <input type="hidden" name="block_data[type]" value="{$type}" />
        <input type="hidden" name="block_data[grid_id]" value="{$grid_id}" />
        
        <div class="select-block-box">
            <div class="bmicon-{$block.type|replace:"_":"-"}"></div>
        </div>
                
        <div class="select-block-description">
            <strong title="{$block.name}">{$block.name|truncate:20:"...":true|escape:html|replace:'...':'&hellip;' nofilter}</strong>
            <p>{$block.description}</p>
        </div>
    </div>
{/foreach}