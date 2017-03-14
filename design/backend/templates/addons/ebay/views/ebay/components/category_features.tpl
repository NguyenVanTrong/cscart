<div id="box_ebay_cf_{$data_id}">
{if $current_category}
    {assign var="features" value=$current_category.features}
    {assign var="paypal_required" value=$features.paypal_required}

    {if $features.payment_methods}
    <div class="control-group" >
        <label class="control-label cm-required" for="elm_ebay_payment_methods">{__("ebay_payment_methods")}:</label>
        <div class="controls">
        <select size="5" id="elm_ebay_payment_methods" name="template_data[payment_methods][]" multiple="multiple">
            {foreach from=$features.payment_methods item="payment"}
                <option {if (is_array($template_data.payment_methods) && in_array($payment, $template_data.payment_methods) && !$paypal_required) || ($paypal_required && $payment == 'PayPal')}selected="selected"{/if} value="{$payment}">{$payment}</option>
            {/foreach}
        </select>
        {if $paypal_required}<p class="ebay_paypal_notice muted">{__('paypal_required_and_selected')}</p>{/if}
        </div>
    </div>
    {/if}

    {if $paypal_required || (is_array($features.payment_methods) && in_array('PayPal', $features.payment_methods))}
    <div class="control-group">
        <label for="elm_paypal_email" id="label_elm_paypal_email" class="control-label cm-email {if $paypal_required || (is_array($template_data.payment_methods) && in_array('PayPal', $template_data.payment_methods))}cm-required{/if}">{__("paypal_email")}:</label>
        <div class="controls">
            <input type="text" data-required="{$paypal_required}" id="elm_paypal_email" name="template_data[paypal_email]" class="input-large" size="32" maxlength="128" value="{$template_data.paypal_email}" />
        </div>
    </div>
    {/if}
    
    {if $features.condition_enabled && $features.conditions}
    <div class="control-group" >
        <label class="control-label" for="elm_ebay_condition">{__("ebay_category_condition")}:</label>
        <div class="controls">
        <select id="elm_ebay_condition" name="template_data[condition_id]">
            {foreach from=$features.conditions key="id" item="condition"}
                <option {if $template_data.condition_id == $id}selected="selected"{/if} value="{$id}">{$condition}</option>
            {/foreach}
        </select>
        </div>
    </div>
    {/if}

    {if $features.listing_duration}
    <div class="control-group" id="ebay_duration">
        <label class="control-label cm-required" for="elm_ebay_duration">{__("ebay_duration")}:</label>
        <div class="controls">
        <select id="elm_ebay_duration" name="template_data[ebay_duration]">
            {foreach from=$features.listing_duration item="item"}
            <option {if $template_data.ebay_duration == $item}selected="selected"{/if} value="{$item}">{$item}</option>
            {/foreach}
        </select>
        </div>
    </div>
    {/if}

{/if}
<!--box_ebay_cf_{$data_id}--></div>
<script type="application/javascript">
    (function(_, $) {
        $(document).ready(function() {
            $('body').on('change', '#elm_ebay_payment_methods', function() {
                var value = $(this).val(),
                    $label = $('#label_elm_paypal_email'),
                    $input = $('#elm_paypal_email');

                if ($label.length && !$input.data('required')) {
                    if (value && $.inArray('PayPal', $(this).val()) != -1) {
                        $label.addClass('cm-required');
                    } else {
                        $label.removeClass('cm-required');
                    }
                }
            });
        });
    }(Tygh, Tygh.$));
</script>
