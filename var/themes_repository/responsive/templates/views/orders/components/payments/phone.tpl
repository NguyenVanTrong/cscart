<div class="ty-control-group">
    <label for="customer_phone" class="ty-control-group__title">{__("phone")}</label>
    <input id="customer_phone" size="35" type="text" name="payment_info[customer_phone]" value="{if !$cart.payment_info.customer_phone && $cart.user_data.phone}{$cart.user_data.phone}{else}{$cart.payment_info.customer_phone}{/if}" class="ty-input-text cm-autocomplete-off" />
</div>