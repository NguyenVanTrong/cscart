<div id="content_keys_{$id}">
    
    {if $providers_schema[$provider].instruction}
    <div class="control-group">
        <div class="controls">
            <div class="alert alert-block hybrid-auth-instruction">
                {__($providers_schema[$provider].instruction)}
            </div>
        </div>
    </div>
    {/if}

    {foreach from=$providers_schema[$provider].keys item="key" key="key_id"}
        <div class="control-group">
            <label for="section_{$key_id}_{$id}" class="control-label{if $key.required} cm-required{/if}">{__($key.label)}:</label>
            <div class="controls">
                <input type="text" name="provider_data[{$key.db_field}]" size="30" value="{$provider_data[$key.db_field]}" id="section_{$key_id}_{$id}">
            </div>
        </div>
    {/foreach}
<!--content_keys_{$id}--></div>