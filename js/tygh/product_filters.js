(function (_, $) {
    'use strict';

    var base_url;
    var ajax_ids;
    var no_trigger = false;

    var HASH_SEPARATOR = '_';
    var HASH_FEATURE_SEPARATOR = '-';

    (function ($) {

        function generateHash(container) {
            var features = {};
            var hash = [];

            container.find('input.cm-product-filters-checkbox:checked').each(function () {
                var elm = $(this);
                if (!features[elm.data('caFilterId')]) {
                    features[elm.data('caFilterId')] = [];
                }
                features[elm.data('caFilterId')].push(elm.val());
            });

            for (var k in features) {
                hash.push(k + HASH_FEATURE_SEPARATOR + features[k].join(HASH_FEATURE_SEPARATOR));
            }

            return hash.join(HASH_SEPARATOR);
        }

        function resetFilters(obj) {
            obj.prop('checked', !obj.prop('checked'));
            if (obj.data('prevVal')) {
                no_trigger = true;

                var vals = obj.data('prevVal').split('-');
                var sli = obj.parent().find('.cm-range-slider');
                if (sli.length) {
                    sli.slider('values', [vals[0], vals[1]]);
                    sli.slider('option', 'slide').call(sli, {}, {values: [vals[0], vals[1]]}); // that's so dirty, but it works
                }

                var da = obj.parent().find('.cm-date-range');
                if (da.length) {
                    da.daterangepicker({
                        startDate: vals[0],
                        endDate: vals[1]
                    });
                }

                no_trigger = false;
            }
        }

        function getProducts(url, obj) {
            if (ajax_ids) {
                $.ceAjax('request', url, {
                    result_ids: ajax_ids,
                    full_render: true,
                    save_history: true,
                    caching: false,
                    scroll: '.ty-mainbox-title',
                    obj: obj,
                    callback: function (response) {
                        if (response.no_products) {
                            resetFilters(obj);
                        }
                    }
                });
            } else {
                $.redirect(url);
            }

            return false;
        }

        function setHandler() {
            $(_.doc).on('change', '.cm-product-filters-checkbox', function () {
                if (no_trigger) {
                    return false;
                }

                var self = $(this);
                var container = self.parents('.cm-product-filters');

                return getProducts($.attachToUrl(base_url, 'features_hash=' + generateHash(container)), self);
            });
        }

        function setCallback() {
            // re-init filters
            $.ceEvent('on', 'ce.commoninit', function (context) {

                context.find('.cm-product-filters').each(function () {
                    var self = $(this);
                    if (self.data('caBaseUrl')) {
                        base_url = self.data('caBaseUrl');
                        ajax_ids = self.data('caTargetId');
                    }
                });

                initSlider(context);
            });

            $.ceEvent('on', 'ce.filterdate', function (elm, time_from, time_to) {
                var cb = $('#elm_checkbox_' + elm.prop('id'));

                cb.data('prevVal', cb.val());
                cb.val(time_from + '-' + time_to).prop('checked', true).trigger('change');
            });
        }

        function initSlider(parent) {
            parent.find('.cm-range-slider').each(function () {
                var $el = $(this);
                var id = $el.prop('id');
                var json_data = $('#' + id + '_json').val();
                if ($el.data('uiSlider') || !json_data) {
                    return false;
                }
                var data = $.parseJSON(json_data) || null;
                if (!data) {
                    return false;
                }

                $el.slider({
                    disabled: data.disabled,
                    range: true,
                    min: data.min,
                    max: data.max,
                    step: data.step,
                    values: [data.left, data.right],
                    slide: function (event, ui) {
                        $('#' + id + '_left').val(ui.values[0]);
                        $('#' + id + '_right').val(ui.values[1]);
                    },
                    change: function (event, ui) {
                        var replacement = ui.values[0] + '-' + ui.values[1];

                        if (data.extra) {
                            replacement = replacement + '-' + data.extra;
                        }

                        var $checkbox = $('#elm_checkbox_' + id);
                        $checkbox.data('prevVal', $checkbox.val());
                        $checkbox.val(replacement).prop('checked', true).trigger('change');
                    }
                });

                if (data.left != data.min || data.right != data.max) {
                    var replacement = data.left + '-' + data.right;
                    if (data.extra) {
                        replacement = replacement + '-' + data.extra;
                    }

                    $('#elm_checkbox_' + id).val(replacement).prop('checked', true);
                }

                $('#' + id + '_left, #' + id + '_right')
                    .off('change')
                    .on('change', function () {
                        var index = $(this).attr('id') == id + '_left' ? 0 : 1;
                        $el.slider('values', index, _.toNumeric($(this).val()));
                    });

                if ($el.parents('.filter-wrap').hasClass('open')) {
                    $el.parent('.price-slider').show();
                }
            });
        }

        setCallback();
        setHandler();
    })($);

}(Tygh, Tygh.$));
