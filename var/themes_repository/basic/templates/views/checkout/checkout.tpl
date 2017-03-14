{script src="js/tygh/exceptions.js"}
{script src="js/tygh/checkout.js"}

{$smarty.capture.checkout_error_content nofilter}
<a name="checkout_top"></a>
{include file="views/checkout/components/checkout_steps.tpl"}

{capture name="mainbox_title"}<span class="secure-page-title">{__("secure_checkout")}<i class="icon-lock"></i></span>{/capture}
