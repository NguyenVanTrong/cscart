{if is_array($providers_list)}
    {if !isset($redirect_url)}
        {assign value= $config.current_url var="redirect_url"}
    {/if}
    {__("hybrid_auth.social_login")}:
    <p class="ty-text-center">{$smarty.capture.hybrid_auth nofilter}
    {strip}
    <input type="hidden" name="redirect_url" value="{$redirect_url}" />
	{foreach from=$providers_list item="provider_data"}
        {if $provider_data.status == 'A'}
            <a class="cm-login-provider ty-hybrid-auth__icon" data-idp="{$provider_data.provider}"><img src="{$provider_data.icon}" title="{$provider_data.provider}" alt="{$provider_data.provider}" /></a>
	    {/if}
    {/foreach}
    {/strip}
    </p>
{/if}