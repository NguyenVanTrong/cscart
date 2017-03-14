{if $providers_list}
    {include file="common/subheader.tpl" title=__("hybrid_auth.link_provider")}
    <p>{__("hybrid_auth.text_link_provider")}</p>

    <div class="clearfix ty-hybrid-auth__icon-container" id="hybrid_providers">
        {foreach from=$providers_list item="provider_data"}
        {if in_array($provider_data.provider, $linked_providers)}
        <div class="ty-hybrid-auth__icon float-left">
            <a class="cm-unlink-provider ty-hybrid-auth__remove" data-idp="{$provider_data.provider}"><i class="icon-cancel-circle"></i></a>
            <img src="{$images_dir}/addons/hybrid_auth/icons/{$addons.hybrid_auth.icons_pack}/{$provider_data.provider}.png" title="{__("hybrid_auth.linked_provider")}" alt="{$provider_data.provider}"/>
        </div>
        {/if}
        {/foreach}
        <div class="ty-hybrid-auth__icon float-left">&nbsp;</div>
        {foreach from=$providers_list item="provider_data"}
        {if !in_array($provider_data.provider, $linked_providers)}
        <div class="ty-hybrid-auth__icon float-left">

            <a class="cm-link-provider ty-link-unlink-provider" data-idp="{$provider_data.provider}">
                <i class="ty-hybrid-auth__add icon-plus-circle"></i>
                <img src="{$images_dir}/addons/hybrid_auth/icons/{$addons.hybrid_auth.icons_pack}/{$provider_data.provider}.png" title="{__("hybrid_auth.not_linked_provider")}" alt="{$provider_data.provider}"/>
            </a>
        </div>
        {/if}
        {/foreach}
    <!--hybrid_providers--></div>
{/if}