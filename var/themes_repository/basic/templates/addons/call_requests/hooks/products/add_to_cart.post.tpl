{if !$hide_form && $addons.call_requests.buy_now_with_one_click == "Y"}

    {include file="common/popupbox.tpl"
        href="call_requests.request?product_id={$product.product_id}&obj_prefix={$obj_prefix}"
        link_text=__("call_requests.buy_now_with_one_click")
        text=__("call_requests.buy_now_with_one_click")
        id="call_request_{$obj_prefix}{$product.product_id}"
        link_meta="ty-btn ty-btn__text ty-cr-buy-now-btn"
        content=""
    }

{/if}
