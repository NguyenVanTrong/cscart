{script src="js/tygh/exceptions.js"}
{script src="js/tygh/checkout.js"}

{$smarty.capture.checkout_error_content nofilter}
{include file="views/checkout/components/checkout_steps.tpl"}

{capture name="mainbox_title"}<span class="ty-checkout__title">{__("secure_checkout")}&nbsp;<i class="ty-checkout__title-icon ty-icon-lock"></i></span>{/capture}
