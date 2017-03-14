<div class="clearfix" id="balance_total">
    <table width="100%">
        <thead>
        <tr>
            <th></th>
            <th width="15%" class="right"><h4>Totals</h4></th>
        </tr>
        </thead>
        {if isset($totals.income_carried_forward)}
            <tr>
                <td class="right">{__("vendor_payouts.income_carried_forward")}:</td>
                <td class="right">{include file="common/price.tpl" value=$totals.income_carried_forward}</td>
            </tr>
        {/if}
        {if isset($totals.income)}
            <tr>
                <td class="right"><h4>{__("vendor_payouts.income")}:</h4></td>
                <td class="right"><h4 class="text-{if $totals.income > 0}success{else}error{/if}">{include file="common/price.tpl" value=$totals.income}</h4></td>
            </tr>
        {/if}
        {if isset($totals.balance_carried_forward)}
            <tr>
                <td class="right">{__("vendor_payouts.balance_carried_forward")}:</td>
                <td class="right">{include file="common/price.tpl" value=$totals.balance_carried_forward}</td>
            </tr>
        {/if}
        {if isset($totals.balance)}
            <tr>
                <td class="right"><h4>{__("vendor_payouts.balance")}:</h4></td>
                <td class="right"><h4 class="text-{if $totals.balance > 0}success{else}error{/if}">{include file="common/price.tpl" value=$totals.balance}</h4></td>
            </tr>
        {/if}
    </table>
<!--balance_total--></div>
