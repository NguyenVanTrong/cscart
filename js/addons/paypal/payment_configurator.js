function fn_paypal_add_redesign_handlers(app) {
    $('[data-ca-form-group="processor"] select').each(function (i, elm) {
        if (!$(elm).data('ca-processor-procesed')) {
            $(elm).data('ca-processor-procesed', true);

            $(elm).change(function (e) {
                fn_paypal_perform_redesign(
                    app,
                    $(elm).closest('[data-ca-form-group="main"]'),
                    $(elm).val()
                );
            });
        }
    });
}

function fn_paypal_perform_redesign(app, container, processor) {
    var scheme;
    if (app.paypal_processor_ids.indexOf(processor) == -1) {
        scheme = fn_paypal_get_redesign_scheme(app, 'common');
    } else {
        scheme = fn_paypal_get_redesign_scheme(app, 'paypal');
    }

    var src, dst, val;
    for (var i in scheme.rearrange) {
        src = $('[data-ca-form-group="' + scheme.rearrange[i] + '"]', container);
        if (src.length > 0) {
            if (i == 0) {
                src.prependTo(container);
            } else {
                dst = $('[data-ca-form-group="' + scheme.rearrange[i - 1] + '"]', container);
                if (dst.length > 0) {
                    dst.after(src);
                }
            }
        }
    }
    for (i in scheme.callbacks) {
        scheme.callbacks[i](container);
    }
}

function fn_paypal_get_redesign_scheme(app, direction) {

    if (direction == 'paypal') {

        return {
            rearrange: ['processor', 'name', 'company', 'tpl', 'category', 'icon', 'status', 'description',
                'instructions', 'usergroup', 'taxes', 'surcharge', 'surcharge_title'
            ],
            callbacks: [
                function (container) { // rename labels
                    var dst;
                    dst = $('[data-ca-form-group="description"] label', container);
                    dst.length > 0 && dst.text(app.tr('addons_paypal_display_description'));
                    dst = $('[data-ca-form-group="name"] label', container);
                    dst.length > 0 && dst.text(app.tr('addons_paypal_display_name'));
                },
                function (container) { // set payment name as a processor name
                    var dst = $('[data-ca-form-group="name"] input', container);
                    var src = $('[data-ca-form-group="processor"] option:selected', container);
                    if (dst.length > 0 && src.length > 0 && dst.val() == '') {
                        dst.val(src.text());
                    }
                },
                function (container) { // set paypal icon as a default icon
                    var dst = $('[data-ca-form-group="icon"] .no-image', container);
                    var payment_id = $('[name="payment_id"]', container.closest('form')).val();
                    if (dst.length > 0 && payment_id == 0) {
                        dst.addClass('no-image-paypal');
                    }
                }
            ]
        };
    } else {

        return {
            rearrange: ['name', 'company', 'processor', 'tpl', 'category', 'usergroup', 'description',
                'update_divider', 'surcharge', 'surcharge_title', 'taxes', 'instructions', 'status', 'icon'
            ],
            callbacks: [
                function (container) { // rename labels
                    var dst;
                    dst = $('[data-ca-form-group="description"] label', container);
                    dst.length > 0 && dst.text(app.tr('description'));
                    dst = $('[data-ca-form-group="name"] label', container);
                    dst.length > 0 && dst.text(app.tr('name'));
                },
                function (container) { // remove paypal icon
                    var dst = $('[data-ca-form-group="icon"] .no-image', container);
                    if (dst.length > 0) {
                        dst.removeClass('no-image-paypal');
                    }
                }
            ]
        };
    }
}