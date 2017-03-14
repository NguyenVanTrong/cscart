{if $product.divido_data.show_calculator}
    <div
            class="ty-divido__price"
            data-divido-widget
            data-divido-api-key="{$product.divido_data.api_key}"
            data-divido-amount="{$product.divido_data.price}"
    ></div>
    {script src="js/addons/divido/widget.js" class="cm-ajax-force"}
{/if}