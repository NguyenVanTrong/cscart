{include file="common/letter_header.tpl"}

{__("hello")},
<br />
<br />
{__("vendor_payouts.new_withdrawal_requested_text", ["[amount]" => $payment.amount, "[requester]" => $payment.initiator])}.
{__("vendor_payouts.view_details")}: <a href="{$accounting_url}">{$accounting_url}</a>
{if $payment.comments}
    <br />
    <br />
    {__("vendor_payouts.withdrawal_comments")}:
    <br />
    {$payment.comments}
{/if}

{include file="common/letter_footer.tpl"}