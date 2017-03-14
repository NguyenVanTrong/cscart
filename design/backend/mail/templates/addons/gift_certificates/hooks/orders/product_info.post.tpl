{if $oi.extra.in_use_certificate}
    <div style="unicode-bidi: bidi-override;">;<span style="unicode-bidi: embed;"><span style="unicode-bidi: embed;">({__("gift_certificates")}:</span>&nbsp;<span style="unicode-bidi: embed;">{foreach from=$oi.extra.in_use_certificate item="c" key="c_key" name="f_fciu"}&nbsp;{$c_key}{if !$smarty.foreach.f_fciu.last},{/if}{/foreach})</span></div>
{/if}