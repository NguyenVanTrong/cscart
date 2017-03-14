{if $payment_method.processor_params.in_context == 'Y' && !$smarty.session.pp_express_details}
    <script type="text/javascript">
        (function(_, $) {
            if (!_.embedded) {
                if (window.paypalCheckoutReady) {
                    $.redirect(_.current_url);
                } else {
                    window.paypalCheckoutReady = function() {
                        paypal.checkout.setup("{$payment_method.processor_params.merchant_id}", {
                            environment: "{if $payment_method.processor_params.mode == 'live'}production{else}sandbox{/if}",
                            buttons: [{
                                button: 'place_order_{$tab_id}',
                                click: function(e) {
                                    e.preventDefault();
                                    paypal.checkout.initXO();

                                    $.ceAjax("request", "{'paypal_express.express'|fn_url}", {
                                        method: "post",
                                        data: {
                                            in_context: 1,
                                            in_context_order: 1,
                                            payment_id: "{$payment_id}"
                                        },
                                        callback: function(response) {
                                            var data = JSON.parse(response.text);
                                            if (data.token) {
                                                var url = paypal.checkout.urlPrefix + data.token;
                                                paypal.checkout.startFlow(url);
                                            }
                                            if (data.error) {
                                                paypal.checkout.closeFlow();
                                            }
                                        }
                                    });
                                }
                            }]
                        });
                    };
                    $.getScript('//www.paypalobjects.com/api/checkout.js');
                }
            }
        })(Tygh, Tygh.$);
    </script>
{/if}