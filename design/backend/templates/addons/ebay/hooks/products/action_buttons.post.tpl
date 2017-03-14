{if $products}
    <li>{btn type="list" class="cm-process-items cm-ajax cm-comet" text=__("export_products_to_ebay") dispatch="dispatch[ebay.export]" form="manage_products_form"}</li>
    <li>{btn type="list" class="cm-process-items cm-ajax cm-comet" text=__("ebay_end_products_on_ebay") dispatch="dispatch[ebay.end_products]" form="manage_products_form"}</li>
    <li>{btn type="list" class="cm-process-items cm-ajax cm-comet" text=__("ebay_sync_products_status") dispatch="dispatch[ebay.update_product_status]" form="manage_products_form"}</li>
{/if}