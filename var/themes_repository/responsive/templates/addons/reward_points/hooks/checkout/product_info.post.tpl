{if !$cart.products.$key.extra.configuration}
    {if $cart.products.$key.extra.points_info.price}
    <div class="ty-reward-points__product-info">
        <strong class="ty-control-group__label">{__("price_in_points")}:</strong>
        <span class="ty-control-group__item" id="price_in_points_{$key}">{__("points_lowercase", [$cart.products.$key.extra.points_info.display_price])}</span>
    </div>
    {/if}
    {if $cart.products.$key.extra.points_info.reward}
    <div class="ty-reward-points__product-info">
        <strong class="ty-control-group__label">{__("reward_points")}:</strong>
        <span class="ty-control-group__item" id="reward_points_{$key}">{__("points_lowercase", [$cart.products.$key.extra.points_info.reward])}</span>
    </div>
    {/if}
{/if}