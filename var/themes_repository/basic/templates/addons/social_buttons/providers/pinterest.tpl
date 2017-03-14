{if $addons.social_buttons.pinterest_enable == "Y" && $provider_settings.pinterest.data}
<a href="//ru.pinterest.com/pin/create/button/" {$provider_settings.pinterest.data nofilter}><img src="//assets.pinterest.com/images/pidgets/pinit_fg_en_rect_red_{$provider_settings.pinterest.size}.png" /></a>
<script type="text/javascript" class="cm-ajax-force">
    (function(d){
        var f = d.getElementsByTagName('SCRIPT')[0], p = d.createElement('SCRIPT');
        p.type = 'text/javascript';
        p.async = true;
        p.src = '//assets.pinterest.com/js/pinit.js';
        f.parentNode.insertBefore(p, f);
    }(document));
</script>
{/if}
