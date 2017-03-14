{if $show_rating}

    {if $product.discussion_type && $product.discussion_type == "R" || $product.discussion_type == "B"}
        {if $product.average_rating}
            {$average_rating = $product.average_rating}
        {elseif $product.discussion.average_rating}
            {$average_rating = $product.discussion.average_rating}
        {/if}

        {if $average_rating > 0}
            {include file="addons/discussion/views/discussion/components/stars.tpl"
                stars=$average_rating|fn_get_discussion_rating
                link="products.view?product_id={$product.product_id}&selected_section=discussion#discussion"
            }
        {/if}

    {/if}

{/if}
