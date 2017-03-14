{$carrier_info = ""}

{hook name="carriers:list"}

{if $carrier == "usps"}
    {$url = "https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=`$tracking_number`"}
    {$carrier_name = __("carrier_usps")}
{elseif $carrier == "ups"}
    {$url = "http://wwwapps.ups.com/etracking/tracking.cgi?tracknum=`$tracking_number`"}
    {$carrier_name = __("carrier_ups")}
{elseif $carrier == "fedex"}
    {$url = "https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=`$tracking_number`"}
    {$carrier_name = __("carrier_fedex")}
{elseif $carrier == "aup"}
    {$url = "http://auspost.com.au/track/track.html?exp=b&id=`$tracking_number`"}
    {$carrier_name = __("carrier_aup")}
{elseif $carrier == "can"}
    {$url = "https://www.canadapost.ca/cpotools/apps/track/personal/findByTrackNumber?trackingNumber=`$tracking_number`"}
    {$carrier_name = __("carrier_can")}
{elseif $carrier == "dhl" || $shipping.carrier == "ARB"}
    {$url = "http://www.dhl.com/content/g0/en/express/tracking.shtml?AWB=`$tracking_number`&brand=DHL"}
    {$carrier_name = __("carrier_dhl")}
{elseif $carrier == "swisspost"}
    {$url = "http://www.post.ch/swisspost-tracking?formattedParcelCodes=`$tracking_number`"}
    {$carrier_name = __("carrier_swisspost")}
{elseif $carrier == "temando"}
    {$url = "http://temando.com/en/track?token=`$tracking_number`&op=Track+Shipment&form_id=temando_tracking_form"}
    {$carrier_name = __("carrier_temando")}
{else}
    {$url = ""}
    {$carrier_name = $carrier}
{/if}

{/hook}

{hook name="carriers:capture"}
    {capture name="carrier_name"}
        {$carrier_name}
    {/capture}

    {capture name="carrier_url"}
        {$url nofilter}
    {/capture}

    {capture name="carrier_info"}
        {$carrier_info nofilter}
    {/capture}
{/hook}