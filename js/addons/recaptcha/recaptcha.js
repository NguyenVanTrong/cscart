(function (_, $) {
    var pluginName = "ceRecaptcha";

    $.extend(_, {
        // A flag indicating the Recaptcha library is ready to use
        recaptchaLoaded: false,

        // Stores jQuery object instances that required Recaptcha to be applied before Recaptcha was loaded
        recaptchaInitQueue: [],

        // Callback triggered by Recaptcha "onload" event
        onRecaptchaLoaded: function () {
            this.recaptchaLoaded = true;

            if (this.recaptchaInitQueue.length) {
                $.each(this.recaptchaInitQueue, function (a, b) {
                    $(this).ceRecaptcha();
                });
            }
        }
    });


    $.ceEvent('on', 'ce.commoninit', function (context) {

        // Register custom Recaptcha form validator.
        // In order to validation work, Recaptcha container DOM element must have:
        // * an 'id' HTML attribute set;
        // * an associated 'label' tag with 'for' attribute set pointing to Recaptcha container and 'cm-recaptcha cm-required' classes set;
        $.ceFormValidator('registerValidator', {
            class_name: 'cm-recaptcha',
            'func': function (recaptcha_container_id, $container, $label) {
                var recaptcha = $.data($container[0], "plugin_" + pluginName);

                if (recaptcha instanceof ReCaptcha) {
                    return recaptcha.checkIsValidationPassed();
                }

                return true;
            },
            message: _.tr('error_validator_recaptcha')
        });


        $('.cm-recaptcha:not(label)', context).ceRecaptcha();
    });

    // jQuery plugin constructor
    function ReCaptcha(element, options) {
        this.$el = $(element);
        this.settings = $.extend({}, _.recaptcha_settings, options);

        this.grecaptcha = null;
        this.isValidationPassed = null;
    }

    $.extend(ReCaptcha.prototype, {
        init: function (grecaptcha) {
            this.grecaptcha = grecaptcha;
            this.isValidationPassed = false;

            this.render();
        },
        render: function () {
            var self = this;

            grecaptcha.render(this.$el[0], {
                sitekey: this.settings.site_key,
                theme: this.settings.theme,
                size: this.settings.size,
                callback: function () {
                    self.isValidationPassed = true;
                },
                'expired-callback': function () {
                    self.isValidationPassed = false;
                }
            });
        },
        checkIsValidationPassed: function () {
            return this.isValidationPassed;
        }
    });


    // Register jQuery plugin
    $.fn[pluginName] = function (options) {
        var self = this,
            createPluginInstances = function () {
                return self.each(function () {
                    var recaptcha,
                        $el = $(this),
                        el_id = $el.attr('id');

                    if (!el_id) {
                        return;
                    }

                    if (_.recaptchaLoaded) {
                        if (!$.data(this, "plugin_" + pluginName)) {
                            recaptcha = new ReCaptcha(this, options);
                            recaptcha.init(window.grecaptcha);

                            $.data(this, "plugin_" + pluginName, recaptcha);
                        }
                    } else {
                        _.recaptchaInitQueue.push($el)
                    }
                });
            };

        if (this.length) {
            return createPluginInstances();
        }

        return this;
    };

}(Tygh, Tygh.$));