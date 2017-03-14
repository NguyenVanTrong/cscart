{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}

<div class="clearfix">
    <div class="ty-credit-card">
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="eway_cc_number_{$id_suffix}" class="ty-control-group__title cm-required">{__("card_number")}</label>
                <input size="35" type="text" id="eway_cc_number_{$id_suffix}" name="payment_info[card_number]" value="" class="cm-autocomplete-off" />
            </div>
    
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_month_{$id_suffix}" class="ty-control-group__title cm-cc-date cm-cc-exp-month cm-required">{__("valid_thru")}</label>
                <label for="credit_card_year_{$id_suffix}" class="cm-required cm-cc-date cm-cc-exp-year hidden"></label>
                <input type="text" id="credit_card_month_{$id_suffix}" name="payment_info[expiry_month]" value="" size="2" maxlength="2" class="ty-credit-card__input-short " />&nbsp;&nbsp;/&nbsp;&nbsp;<input type="text" id="credit_card_year_{$id_suffix}"  name="payment_info[expiry_year]" value="" size="2" maxlength="2" class="ty-credit-card__input-short" />&nbsp;
            </div>
    
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_name_{$id_suffix}" class="ty-control-group__title cm-required">{__("cardholder_name")}</label>
                <input size="35" type="text" id="credit_card_name_{$id_suffix}" name="payment_info[cardholder_name]" value="" class="cm-cc-name ty-credit-card__input ty-uppercase" />
            </div>
    </div>
    
    <div class="ty-control-group ty-credit-card__cvv-field">
        <label for="eway_cvv2_{$id_suffix}" class="ty-control-group__title cm-required cm-autocomplete-off">{__("cvv2")}</label>
        <input type="text" id="eway_cvv2_{$id_suffix}" name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-autocomplete-off" />
    </div>
</div>
<script class="cm-ajax-force">
(function(_, $) {
    $(document).ready(function(){
        $.getScript("https://secure.ewaypayments.com/scripts/eCrypt.js");
        $("#place_order_{$tab_id}").on('click', function(){
            if ($("#eway_cc_number_{$id_suffix}").attr('data-eway-encrypted') != 'yes') {
                var elm_cvv = $("#eway_cvv2_{$id_suffix}");
                var elm_num = $("#eway_cc_number_{$id_suffix}");
                var cvv_val = elm_cvv.val();
                var num_val = elm_num.val();
                var enc_cvv = eCrypt.encryptValue(cvv_val, '{$cart.payment_method_data.processor_params.encryption_key}');
                var enc_num = eCrypt.encryptValue(num_val, '{$cart.payment_method_data.processor_params.encryption_key}');
                elm_cvv.val(enc_cvv);
                elm_num.val(enc_num);
                elm_cvv.prop('maxlength', enc_cvv.length);
                elm_num.attr('data-eway-encrypted', 'yes');
            }
        });
    });
})(Tygh, Tygh.$);
</script>
