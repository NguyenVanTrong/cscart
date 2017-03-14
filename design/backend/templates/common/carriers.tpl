{if $capture}
{capture name="carrier_field"}
{/if}

<select {if $id}id="{$id}"{/if} name="{$name}" class="{if $meta}{$meta}{/if} form-control">
    <option value="">--</option>
    {foreach from=$carriers key="code" item="carrier_data"}
    	<option value="{$code}" {if $carrier == $code}{$carrier_name = $carrier_data.name}selected="selected"{/if}>{$carrier_data.name}</option>
    {/foreach}
</select>
{if $capture}
{/capture}

{capture name="carrier_name"}
{$carrier_name}
{/capture}
{/if}