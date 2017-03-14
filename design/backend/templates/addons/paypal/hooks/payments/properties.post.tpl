<script type="text/javascript">
    (function (_, $) {
        _.paypal_processor_ids = {''|fn_get_paypal_processors|json_encode nofilter};

        $.ceEvent('on', 'ce.ajaxdone', function (elms, inline_scripts, params, data, response_text) {
            for (var i in elms) {
                fn_paypal_add_redesign_handlers(_);
                $('[data-ca-form-group="processor"] select', elms[i]).trigger('change');
            }
        });

        $(document).ready(function () {
            fn_paypal_add_redesign_handlers(_);
        });
    })(Tygh, Tygh.$);
</script>