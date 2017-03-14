{include file="common/letter_header.tpl"}

{__("call_requests.text_call_request", ["[customer]" => $customer, "[href]" => $url, "[phone_number]" => $phone_number])} <br />
{__("call_requests.text_call_request_call_time", ["[time_from]" => $time_from, "[time_to]" => $time_to])}

{include file="common/letter_footer.tpl"}