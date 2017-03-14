{if $option|fn_needs_image_verification == true}
    {assign var="id" value="iv_"|uniqid}

    {hook name="common:image_verification"}
    {/hook}
{/if}