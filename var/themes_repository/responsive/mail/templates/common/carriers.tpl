{hook name="carriers:list"}

{$carriers_schema = fn_get_schema('shippings', 'carriers')}

{if $carriers_schema.$carrier}
    {$url = $carriers_schema.$carrier.tracking_url_template|replace:"[tracking_number]":$tracking_number}
    {$carrier_name = __("carrier_`$carrier`")}
{else}
    {$url = ""}
    {$carrier_name = $carrier}
{/if}

{/hook}

{capture name="carrier_name"}
{$carrier_name}
{/capture}

{capture name="carrier_url"}
{$url nofilter}
{/capture}