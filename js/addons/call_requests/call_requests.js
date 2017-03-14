(function(_, $){
    $.ceEvent('on', 'ce.commoninit', function(context) {

        var time_elements = context.find('.cm-cr-mask-time'),
            phone_elements = context.find('.cm-cr-mask-phone');

        if (time_elements.length === 0 && phone_elements.length === 0) {
            return true;
        }

        time_elements.mask('99:99');

        if (phone_elements.length && _.call_requests_phone_masks_list) {
            var maskList = $.masksSort(_.call_requests_phone_masks_list, ['#'], /[0-9]|#/, "mask");
            var maskOpts = {
                inputmask: {
                    definitions: {
                        '#': {
                            validator: "[0-9]",
                            cardinality: 1
                        }
                    },
                    showMaskOnHover: false,
                    autoUnmask: false
                },
                match: /[0-9]/,
                replace: '#',
                list: maskList,
                listKey: "mask"
            };

            phone_elements.each(function() {
                if (_.call_phone_mask) {
                    $(this).inputmask({
                        mask: _.call_phone_mask,
                        showMaskOnHover: false,
                        autoUnmask: false
                    });

                } else {
                    $(this).inputmasks(maskOpts);
                }
            });
        }

        if (_.call_phone_mask) {
            $.ceFormValidator('registerValidator', {
                class_name: 'cm-cr-mask-phone-lbl',
                message: _.tr('call_requests.error_validator_phone'),
                func: function(id) {
                    var input = $('#' + id);

                    if (!$.is.blank(input.val())) {
                        return input.inputmask("isComplete");
                    } else {
                        return true;
                    }
                }
            });
        }
    });

    $.ceEvent('on', 'ce.formpre_call_requests_form', function(form, elm) {
        var val_email = form.find('[name="call_data[email]"]').val(),
            val_phone = form.find('[name="call_data[phone]"]').val(),
            allow = !!(val_email || val_phone),
            error_box = form.find('.cm-cr-error-box'),
            dlg = $.ceDialog('get_last');

        error_box.toggle(!allow);
        dlg.ceDialog('reload');

        if (allow) {
            var product_data = $('[name="' + form.data('caProductForm') + '"]').serializeObject();

            $.each(product_data, function(key, value){
                if (key.match(/product_data/)) {
                    form.append('<input type="hidden" name="' + key + '" value="' + value + '" />');
                }
            });
        }

        return allow;
    });

})(Tygh, Tygh.$);

