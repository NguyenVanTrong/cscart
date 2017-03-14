{foreach from=$orders item='order' name='orders'}
{foreach from=$order.products item='item' name='products'}{*
Co./Last Name*}"{$order.lastname}",{*
First Name*}"{$order.firstname}",{*
Addr 1 - Line 1*}"{$order.s_address}",{*
  - Line 2*}"{$order.s_address_2}",{*
  - Line 3*}"{$order.s_city} {$order.s_state_descr}",{*
  - Line 4*}"{$order.s_country_descr} {$order.s_zipcode}",{*
Inclusive*}Y,{*
Invoice No.*}W{$order.order_id|string_format:"%07d"},{*
Date*}{$order.order_date},{*
Customer PO*},{*
Ship Via*},{*
Delivery Status*}P,{*
Item Number*}{$item.product_code},{*
Quantity*}{$item.amount},{*
Description*}"{$item.prod_opts_description}",{*
Price*}{$item.price},{*
Discount*}{$item.discount},{*
Total*}{$item.subtotal},{*
Job*},{*
Comment*},{*
Journal Memo*},{*
Salesperson Last Name*},{*
Salesperson First Name*},{*
Shipping Date*},{*
Referral Source*},{*
Tax Code (AU)/GST Code (NZ)*}{$addons.myob.tax_gst_code},{*
Tax Amount (AU)/GST Amount (NZ)*}{$item.tax_value},{*
Freight Amount*}{if $smarty.foreach.products.last}{$order.shipping_cost}{/if},{*
Freight Tax Code (AU)/Freight GST Code (NZ)*}{$addons.myob.freight_tax_gst_code},{*
Freight Tax Amount (AU)/Freight GST Amount (NZ)*}{if $smarty.foreach.products.last}{$order.shipping_tax}{/if},{*
Sale Status*}I,{*
Currency Code*}{$addons.myob.currency},{*
Exchange Rate*},{*
Terms - Payment is Due*}1,{*
  - Discount Days*}0,{*
  - Balance Due Days*}0,{*
  - % Discount*}0,{*
  - % Monthly Charge*}0,{*
Amount Paid*}{$item.paid_amount},{*
Payment Method*}{$order.payment_method.payment},{*
Payment Notes*},{*
Name on Card*},{*
Card Number*},{*
Authorisation Code*},{*
BSB (AU)*},{*
Account Number*},{*
Drawer/Account Name*},{*
Cheque Number*},{*
Category*},{*
Location ID*},{*
Card ID*}WEB-{$order.user_id|string_format:"%011d"},{*
Record ID*}
{/foreach}
{/foreach}