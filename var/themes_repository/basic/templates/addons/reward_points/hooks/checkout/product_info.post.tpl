{if !$cart.products.$key.extra.configuration}
    {if $cart.products.$key.extra.points_info.price}
    <div class="product-list-field">
        <label for="price_in_points_{$key}" class="valign">{__("price_in_points")}:</label>
        <span id="price_in_points_{$key}">{__("points_lowercase", [$cart.products.$key.extra.points_info.display_price])}</span>
    </div>
    {/if}
    {if $cart.products.$key.extra.points_info.reward}
    <div class="product-list-field">
        <label for="reward_points_{$key}" class="valign">{__("reward_points")}:</label>
        <span id="reward_points_{$key}">{__("points_lowercase", [$cart.products.$key.extra.points_info.reward])}</span>
    </div>
    {/if}
{/if}