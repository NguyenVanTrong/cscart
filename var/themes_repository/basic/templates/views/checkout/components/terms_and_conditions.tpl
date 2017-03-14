{if $settings.Checkout.agree_terms_conditions == "Y"}
    <div class="control-group terms">
        {hook name="checkout:terms_and_conditions"}
        {strip}
        <label for="id_accept_terms{$suffix}" class="cm-check-agreement">
            <input type="checkbox" id="id_accept_terms{$suffix}" name="accept_terms" value="Y" class="cm-agreement checkbox" {if $iframe_mode}onclick="fn_check_agreements('{$suffix}');"{/if} />
            {capture name="terms_link"}
                <a id="sw_terms_and_conditions_{$suffix}" class="cm-combination ty-dashed-link">
                    {__("checkout_terms_n_conditions_name")}
                </a>
            {/capture}
            {__("checkout_terms_n_conditions", ["[terms_href]" => $smarty.capture.terms_link])}
        </label>
        {/strip}

        <div class="hidden" id="terms_and_conditions_{$suffix}">
            {__("terms_and_conditions_content") nofilter}
        </div>
        {/hook}
    </div>
{/if}

{hook name="checkout:terms_and_conditions_extra"}{/hook}

{if $cart_agreements}
    <div class="control-group license-agreement">
        {hook name="checkout:terms_and_conditions_downloadable"}
        {strip}
        <label for="product_agreements_{$suffix}" class="cm-check-agreement">
            <input type="checkbox" id="product_agreements_{$suffix}" name="agreements[]" value="Y" class="cm-agreement checkbox"  {if $iframe_mode}onclick="fn_check_agreements('{$suffix}');"{/if}/>
            <span>{__("checkout_edp_terms_n_conditions")}</span>&nbsp;
            <a id="sw_elm_agreements_{$suffix}" class="cm-combination link-dashed">{__("license_agreement")}</a>
        </label>
        {/strip}
        {/hook}
        <div class="hidden" id="elm_agreements_{$suffix}">
        {foreach from=$cart_agreements item="product_agreements"}
            {foreach from=$product_agreements item="agreement"}
                <p>{$agreement.license nofilter}</p>
            {/foreach}
        {/foreach}
        </div>
    </div>
{/if}

<script type="text/javascript">
    (function(_, $) {
        $.ceFormValidator('registerValidator', {
            class_name: 'cm-check-agreement',
            message: '{__("checkout_terms_n_conditions_alert")|escape:javascript}',
            func: function(id) {
                return $('#' + id).prop('checked');
            }
        });
    }(Tygh, Tygh.$));

    {if $iframe_mode}
        function fn_check_agreements(suffix) {
            var checked = true;

            $('form[name=payments_form_' + suffix + '] input[type=checkbox].cm-agreement').each(function(index, value) {
                if($(value).prop('checked') === false) {
                    checked = false;
                }
            });

            $('#payment_method_iframe_' + suffix).toggleClass('hidden', checked);
        }
    {/if}
</script>
