/**
 * Requires "ceObjectSelector" internal plugin to be loaded.
 */

(function (_, $) {
    var defaults = {
        // Select2 settings for parent items select
        parent: {
            loadViaAjax: true,
            enableSearch: false,
            dataUrl: '',
            enableImages: true
        },

        // Select2 settings for child items select
        child: {
            loadViaAjax: true,
            dataUrl: '',
            enableImages: true
        }
    };

    var ChainedPromotionConditionForm = function (config) {
        this.$operatorSelect = $(config.operatorSelect);
        this.$parentSelect = $(config.parentSelect);
        this.$childSelect = $(config.childSelect);
        this.$childInput = $(config.childInput);

        this.settings = $.extend(true, {}, defaults, config.settings || {});
    };

    $.extend(ChainedPromotionConditionForm.prototype, {
        MULTISELECT_OPERATORS: ['in', 'nin'],

        $operatorSelect: null,
        $parentSelect: null,
        $childSelect: null,
        $childInput: null,

        render: function () {
            this.bindEvents();

            var self = this;

            if (this.$parentSelect.val()) {
                var preselection = this.$parentSelect.val();

                this.$parentSelect.empty().val(null).trigger('change');

                $.ceAjax('request', this.settings.parent.dataUrl, {
                    hidden: true,
                    caching: false,
                    data: {
                        preselected: preselection
                    },
                    callback: function (data) {

                        if (data.objects.length) {
                            self.settings.parent.data = data.objects;

                            self.$parentSelect.ceObjectSelector(self.settings.parent);

                            setTimeout(function () {
                                self.initChildSelect(data.objects[0]);
                            }, 200);
                        }
                    }
                });
            } else {
                this.$parentSelect.ceObjectSelector(this.settings.parent);
            }
        },

        bindEvents: function () {
            var self = this;

            this.$parentSelect.on('select2:select', function (e) {
                self.$childSelect.empty().val(null);
                self.$childInput.val(null);

                self.initChildSelect(e.params.data);
            });

            this.$operatorSelect.on('change', function (e) {
                self.$childSelect.empty().val(null);

                self.initChildSelect(self.$parentSelect.select2('data')[0]);
            });

            this.$childSelect.on('select2:select', function (e) {
                self.onChildSelect(e.params.data);
            });
        },

        initChildSelect: function (selectedParentObject) {
            if (this.$childSelect.data('select2')) {
                this.$childSelect.select2('destroy');
            }

            if (selectedParentObject.object.variants) {
                var loadViaAjax = (typeof selectedParentObject.object.variants == 'string');

                var childSelect2Settings = $.extend({}, this.settings.child, {
                    multiple: this.isMultipleSelectOperator(this.getCurrentOperator()),
                    loadViaAjax: loadViaAjax
                });

                if (loadViaAjax) {
                    childSelect2Settings.dataUrl = selectedParentObject.object.variants;
                } else {
                    childSelect2Settings.data = selectedParentObject.object.variants;
                }

                this.$childSelect.attr('multiple', childSelect2Settings.multiple);
                this.$childInput.addClass('hidden');
                this.$childSelect.prop('disabled', false);

                if (loadViaAjax && this.$childSelect.val()) {
                    var self = this;
                    $.ceAjax('request', childSelect2Settings.dataUrl, {
                        hidden: true,
                        caching: false,
                        data: {
                            preselected: this.$childSelect.val(),
                            page_size: 0
                        },
                        callback: function (data) {
                            if (!data.objects.length) {
                                return;
                            }

                            var childPreselectedObjects = data.objects;
                            $.each(childPreselectedObjects, function(i, object) {
                                object.selected = true;
                            });
                            childSelect2Settings.data = childPreselectedObjects;
                            self.$childSelect.ceObjectSelector(childSelect2Settings);
                        }
                    });
                } else {
                    this.$childSelect.ceObjectSelector(childSelect2Settings);
                }
            } else {
                this.$childSelect.prop('disabled', true).hide();
                this.$childInput.prop('disabled', false).removeClass('hidden');
            }
        },

        onChildSelect: function (selectedChildObject) {
            var serializedValue = this.$childSelect.val();

            if (Array.isArray(serializedValue)) {
                serializedValue = serializedValue.join(',');
            }

            this.$childInput
                .prop('disabled', false)
                .val(serializedValue);
        },

        getCurrentOperator: function () {
            return this.$operatorSelect.val();
        },

        isMultipleSelectOperator: function (operator) {
            return (this.MULTISELECT_OPERATORS.indexOf(operator) > -1);
        }
    });


    _.ChainedPromotionConditionForm = ChainedPromotionConditionForm;
})(Tygh, Tygh.$);