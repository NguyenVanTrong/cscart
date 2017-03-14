{if $is_wishlist}
    {"products.quick_view?product_id=`$product.product_id`"}{if $product.combination}{"&combination=`$product.combination`"}{/if}{"&prev_url=`$current_url`"}
{/if}