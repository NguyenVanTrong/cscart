{include file="common/letter_header.tpl"}

{__("vendor_payouts.payout_declined_text", ["[amount]" => $payment.amount, "[date]" => $payment.date])}.

{include file="common/letter_footer.tpl"}