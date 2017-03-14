<p class="nowrap stars">
    {if $link}
        {if ($runtime.controller == "products" || $runtime.controller == "companies") && $runtime.mode == "view"}
            <a class="cm-external-click" data-ca-scroll="content_discussion" data-ca-external-click-id="discussion">
        {else}
            <a href="{$link|fn_url}">
        {/if}
    {/if}

    {section name="full_star" loop=$stars.full}
        <i class="icon-star"></i>
    {/section}

    {if $stars.part}
        <i class="icon-star-half"></i>
    {/if}

    {section name="full_star" loop=$stars.empty}
        <i class="icon-star-empty"></i>
    {/section}
    
    {if $link}
        </a>
    {/if}
</p>
