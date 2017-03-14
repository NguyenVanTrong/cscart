{if $payment_method.processor_params.in_context == 'Y' && !$smarty.session.pp_express_details}
    <script type="text/javascript">
        (function(_, $) {
            if (!_.embedded) {
                if (window.paypalCheckoutReady) {
                    $.redirect(_.current_url);
                } else {
                    window.paypalCheckoutReady = function() {
                        var payment_form = $("form[name='payments_form_{$tab_id}']");

                        paypal.checkout.setup("{$payment_method.processor_params.merchant_id}", {
                            environment: "{if $payment_method.processor_params.mode == 'live'}production{else}sandbox{/if}",
                            buttons: [{
                                button: 'place_order_{$tab_id}',
                                condition: function() {
                                    return $.ceFormValidator('check', {
                                       form: payment_form
                                    });
                                },
                                click: function(e) {
                                    e.preventDefault();

                                    var form_data = {
                                        in_context_order: 1
                                    };
                                    var fields = payment_form.serializeArray();
                                    for (var i in fields) {
                                        form_data[fields[i].name] = fields[i].value;
                                    }
                                    form_data.result_ids = null;

                                    {* window has to be inited in 'click' handler to prevent browser pop-up blocking *}
                                    paypal.checkout.initXO();

                                    $.ceAjax("request", "{'checkout.place_order'|fn_url}", {
                                        method: "post",
                                        data: form_data,
                                        callback: function(response) {
                                            try {
                                                var data = JSON.parse(response.text);
                                                if (data.token) {
                                                    var url = paypal.checkout.urlPrefix + data.token;
                                                    paypal.checkout.startFlow(url);
                                                }
                                                if (data.error) {
                                                    paypal.checkout.closeFlow();
                                                }
                                            } catch (ex) {
                                                paypal.checkout.initXO();
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