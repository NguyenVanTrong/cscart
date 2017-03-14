{if !$smarty.capture.$map_provider_api}
<script src="//api-maps.yandex.ru/2.1/?lang={$smarty.const.CART_LANGUAGE}&key={$settings.store_locator.yandex_key}" type="text/javascript"></script>
{script src="/js/addons/store_locator/yandex.js"}
{capture name="`$map_provider_api`"}Y{/capture}
{/if}

<script type="text/javascript">
    //<![CDATA[
    {literal}
    (function(_, $) {

        options = {
            {/literal}
            'latitude': {$smarty.const.STORE_LOCATOR_DEFAULT_LATITUDE|doubleval},
            'longitude': {$smarty.const.STORE_LOCATOR_DEFAULT_LONGITUDE|doubleval},
            'map_container': '{$map_container}'
            {literal}
        };

        $.ceMap('init', options);
    }(Tygh, Tygh.$));
    {/literal}
    //]]>
</script>
