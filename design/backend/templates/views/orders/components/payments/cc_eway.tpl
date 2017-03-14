{script src="js/lib/inputmask/jquery.inputmask.min.js"}
{script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

{assign var="card_item" value=$cart.payment_info}
{assign var="open_text" value=$card_item.card_number|strpos:'eCrypted' === 0}

<div class="clearfix">
    <div class="credit-card">
            <div class="control-group">
                <label for="eway_cc_number_{$id_suffix}" class="control-label cm-cc-number cm-required cm-autocomplete-off">{__("card_number")}</label>
                <div class="controls">
                    <input size="35" type="text" id="eway_cc_number_{$id_suffix}" name="payment_info[card_number]" value="{$card_item.card_number}" class="input-big"/>
                </div>
                <ul class="cc-icons-wrap cc-icons unstyled" id="cc_icons{$id_suffix}">
                    <li class="cc-icon cm-cc-default"><span class="default">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-visa"><span class="visa">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-visa_electron"><span class="visa-electron">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-mastercard"><span class="mastercard">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-maestro"><span class="maestro">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-amex"><span class="american-express">&nbsp;</span></li>
                    <li class="cc-icon cm-cc-discover"><span class="discover">&nbsp;</span></li>
                </ul>
            </div>

            <div class="control-group">
                <label for="credit_card_month_{$id_suffix}" class="control-label cm-cc-date cm-required">{__("valid_thru")}</label>
                <div class="controls clear">
                    <div class="cm-field-container nowrap">
                        <input type="text" id="credit_card_month_{$id_suffix}" name="payment_info[expiry_month]" value="{if $open_text}{$card_item.expiry_month}{/if}" size="2" maxlength="2" class="input-small" />&nbsp;/&nbsp;<input type="text" id="credit_card_year_{$id_suffix}"  name="payment_info[expiry_year]" value="{if $open_text}{$card_item.expiry_year}{/if}" size="2" maxlength="2" class="input-small" />
                    </div>
                </div>
            </div>

            <div class="control-group">
                <label for="credit_card_name_{$id_suffix}" class="control-label cm-required">{__("cardholder_name")}</label>
                <div class="controls">
                    <input size="35" type="text" id="credit_card_name_{$id_suffix}" name="payment_info[cardholder_name]" value="{$card_item.cardholder_name}" class="cm-cc-name ty-credit-card__input ty-uppercase" />
                </div>
            </div>
    </div>

    <div class="control-group cvv-field">
        <label for="eway_cvv2_{$id_suffix}" class="control-label cm-cc-cvv2 cm-required cm-autocomplete-off">{__("cvv2")}</label>
        <div class="controls">
            <input type="text" id="eway_cvv2_{$id_suffix}" name="payment_info[cvv2]" value="{$card_item.cvv2}" size="4" maxlength="4" class="cm-autocomplete-off" />

            <div class="cvv2">
                <a>{__("what_is_cvv2")}</a>
                <div class="popover fade bottom in">
                    <div class="arrow"></div>
                    <h3 class="popover-title">{__("what_is_cvv2")}</h3>
                    <div class="popover-content">
                        <div class="cvv2-note">
                            <div class="card-info clearfix">
                                <div class="cards-images">
                                    <img src="{$images_dir}/visa_cvv.png" border="0" alt="" />
                                </div>
                                <div class="cards-description">
                                    <strong>{__("visa_card_discover")}</strong>
                                    <p>{__("credit_card_info")}</p>
                                </div>
                            </div>
                            <div class="card-info ax clearfix">
                                <div class="cards-images">
                                    <img src="{$images_dir}/express_cvv.png" border="0" alt="" />
                                </div>
                                <div class="cards-description">
                                    <strong>{__("american_express")}</strong>
                                    <p>{__("american_express_info")}</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script class="cm-ajax-force">
(function(_, $) {

    $.ceEvent('on', 'ce.commoninit', function() {
        var icons = $('#cc_icons{$id_suffix} li');
        var ccNumberInput = $("#eway_cc_number_{$id_suffix}");
        var ccCv2 = $('label[for=eway_cvv2_{$id_suffix}]');
        var ccCv2Input = $("#eway_cvv2_{$id_suffix}");
        var ccMonthInput = $("#credit_card_month_{$id_suffix}");
        var ccYearInput = $("#credit_card_year_{$id_suffix}");

        if (_.isTouch === false && jQuery.isEmptyObject(ccNumberInput.data("_inputmask")) == true) {

            ccMonthInput.inputmask("99", {
                placeholder: ''
            });

            ccYearInput.inputmask("99", {
                placeholder: ''
            });
        }

        ccNumberInput.validateCreditCard(function (result) {
            icons.removeClass('active');
            if (result.card_type) {
                icons.filter('.cm-cc-' + result.card_type.name).addClass('active');
                if (['visa_electron', 'maestro', 'laser'].indexOf(result.card_type.name) != -1) {
                    ccCv2.removeClass("cm-required");
                } else {
                    ccCv2.addClass("cm-required");
                }
            }
        });
    });

    $(document).ready(function(){
        var eway_enc_key = '{$payment_method.processor_params.encryption_key}';
        if (window.tygh_eway_ready) {
            return true;
        }
        window.tygh_eway_ready = true;

        $.getScript("https://secure.ewaypayments.com/scripts/eCrypt.js");
        $('#order_update input[type="submit"]').on('click', function() {
            var elm_num = $("#eway_cc_number_{$id_suffix}");
            if (elm_num.length > 0 && elm_num.attr('data-eway-encrypted') != 'yes') {
                var elm_cvv = $("#eway_cvv2_{$id_suffix}");
                var cvv_val = elm_cvv.val();
                var num_val = elm_num.val().replace(/\s/g, '');
                if (num_val.indexOf('eCrypted') == -1) {
                    var enc_cvv = eCrypt.encryptValue(cvv_val, eway_enc_key);
                    var enc_num = eCrypt.encryptValue(num_val, eway_enc_key);
                    elm_cvv.inputmask('remove');
                    elm_num.inputmask('remove');
                    elm_cvv.val(enc_cvv);
                    elm_num.val(enc_num);
                    elm_cvv.prop('maxlength', enc_cvv.length);
                    elm_num.attr('data-eway-encrypted', 'yes');
                }
            }
        });
    });
})(Tygh, Tygh.$);
</script>
