{math equation="x+y" x=$cart.total y=$cart.payment_surcharge assign="_total"}
{assign var="_total" value="{fn_format_price_by_currency($_total, $primary_currency, $payment_method.processor_params.currency)}"}
{assign var="api_key" value="{fn_divido_slice_api_key($payment_method.processor_params.api_key)}"}

<input type="hidden" name="payment_info[finance_code]" value="{$cart.payment_info.finance_code}" />
<input type="hidden" name="payment_info[deposit_amount]" value="{$cart.payment_info.deposit_amount}" />

{if !$cart.order_id}
<fieldset id="divido-checkout" data-divido-api-key="{$api_key}" data-divido-calculator class="hidden divido-calculator divido-theme-blue" data-divido-amount="{$_total|default:$cart.total}" data-divido-filter-plans="1">
    <h4>
        <a href="https://www.divido.com" target="_blank" class="divido-logo divido-logo-sm divido-logo">{__("addons.divido")}</a>
        {__("addons.divido.pay_in_instalments")}
    </h4>
    <div class="ty-divido-both"></div>
    <dl>
        <dt><span data-divido-choose-finance data-divido-label="{__("addons.divido.choose_your_plan")}" data-divido-form="divido_finance" class="divido-select"></span></dt>
        <dd><span class="divido-deposit" data-divido-choose-deposit data-divido-label="{__("addons.divido.choose_your_deposit")}" data-divido-form="divido_deposit"></span></dd>
    </dl>
    <div class="description">
        <strong>
            <span data-divido-agreement-duration></span>&nbsp;{__("addons.divido.monthly_payments_of")}&nbsp;<span data-divido-monthly-instalment></span>
        </strong>
    </div>
    <div class="divido-info">
        <dl>
            <dt>{__("addons.divido.term")}</dt>
            <dd><span data-divido-agreement-duration></span> {__("months")}</dd>
            <dt>{__("addons.divido.monthly_instalment")}</dt>
            <dd><span data-divido-monthly-instalment></span></dd>
            <dt>{__("addons.divido.deposit")}</dt>
            <dd><span data-divido-deposit></span></dd>
            <dt>{__("addons.divido.cost_of_credit")}</dt>
            <dd><span data-divido-finance-cost-rounded></span></dd>
            <dt>{__("addons.divido.total_payable")}</dt>
            <dd><span data-divido-total-payable-rounded></span></dd>
            <dt>{__("addons.divido.total_interest_apr")}</dt>
            <dd><span data-divido-interest-rate></span></dd>
        </dl>
    </div>
    <div class="clear"></div>
    <p>{__("addons.divido.text_redirected")}</p>
</fieldset>

{script src="js/addons/divido/divido_calculator.js" class="cm-ajax-force"}
{/if}
