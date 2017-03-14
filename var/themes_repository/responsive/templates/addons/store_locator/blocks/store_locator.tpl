{** block-description:store_locator **}

<form action="{""|fn_url}" method="get" name="store_locator_form">
    <div class="ty-control-group">
        <label for="store_locator_search{$block.block_id}" class="ty-control-group__title">{__("search")}</label>

        <div class="ty-input-append ty-m-none">
            <input type="text" size="20" class="ty-input-text" id="store_locator_search{$block.block_id}" name="q" value="{$store_locator_search.q}" />
            {include file="buttons/go.tpl" but_name="store_locator.search" alt=__("search")}
        </div>

    </div>
</form>