/* editior-description:text_tinymce */
(function (_, $) {

    // FIXME: when jQuery UI will be updated from 1.11.1 version, remove the code below.
    $.widget( "ui.dialog", $.ui.dialog, {
        /*! jQuery UI - v1.10.2 - 2013-12-12
         *  http://bugs.jqueryui.com/ticket/9087#comment:27 - bugfix
         *  http://bugs.jqueryui.com/ticket/4727#comment:23 - bugfix
         *  allowInteraction fix to accommodate windowed editors
         */
        _allowInteraction: function( event ) {
            if ( this._super( event ) ) {
                return true;
            }

            // address interaction issues with general iframes with the dialog
            if ( event.target.ownerDocument != this.document[ 0 ] ) {
                return true;
            }

            // address interaction issues with dialog window
            if ( $( event.target ).closest( ".mce-container" ).length ) {
                return true;
            }

            // address interaction issues with iframe based drop downs in IE
            if ( $( event.target ).closest( ".mce" ).length ) {
                return true;
            }
        },
        /*! jQuery UI - v1.10.2 - 2013-10-28
         *  http://dev.ckeditor.com/ticket/10269 - bugfix
         *  moveToTop fix to accommodate windowed editors
         */
        _moveToTop: function ( event, silent ) {
            if ( !event || !this.options.modal ) {
                this._super( event, silent );
            }
        }
    });


    var support_langs = ['ar', 'hy', 'az', 'eu', 'be', 'bs', 'ca', 'hr', 'cs', 'da', 'dv', 'nl', 'et', 'fo', 'fi', 'gl', 'de', 'el', 'id', 'it', 'ja', 'kk', 'lv', 'lt', 'lb', 'fa', 'pl', 'ro', 'ru', 'sr', 'sk', 'es', 'tg', 'ta', 'ug', 'uk', 'vi', 'cy', 'fr', 'ka', 'he', 'hu', 'is', 'bg', 'zh', 'en', 'km', 'ko', 'ml', 'nb', 'pt', 'si', 'sl', 'sv', 'ta', 'th', 'tr'];
    var lang_map = {
        'fr': 'fr_FR',
        'ka': 'ka_GE',
        'he': 'he_IL',
        'hu': 'hu_HU',
        'is': 'is_IS',
        'bg': 'bg_BG',
        'zh': 'zh_CN',
        'en': 'en_GB',
        'km': 'km_KH',
        'ko': 'ko_KR',
        'ml': 'ml_IN',
        'nb': 'nb_NO',
        'pt': 'pt_PT',
        'si': 'si_LK',
        'sl': 'sl_SI',
        'sv': 'sv_SE',
        'ta': 'ta_IN',
        'th': 'th_TH',
        'tr': 'tr_TR'
    };

    var lang = fn_get_listed_lang(support_langs);
    if (lang in lang_map) {
        lang = lang_map[lang];
    }

    var editor = {
        editorName: 'tinymce',
        is_destroying: false,
        params: {
            plugins: [
                "advlist autolink lists link image charmap print preview anchor",
                "searchreplace visualblocks code fullscreen",
                "insertdatetime media table contextmenu paste textcolor"
            ],
            menubar: false,
            statusbar: true,
            mode: "textareas",
            force_p_newlines: true,
            extended_valid_elements: "i[*],span[*]",
            forced_root_block: '',
            media_strict: false,
            toolbar: undefined,
            resize: true,
            theme: 'modern',
            language: lang,
            strict_loading_mode: true,
            convert_urls: false,
            remove_script_host: false,
            body_class: 'wysiwyg-content',
            //content_css: $.ceEditor('content_css').join(),

            file_picker_callback: function (callback, value, meta) {
                tinyMCE.activeEditor.windowManager.open({
                    file: _.current_location + '/js/lib/elfinder/elfinder.tinymce.html',
                    title: _.tr('file_browser'),
                    width: 600,
                    height: 450,
                    resizable: 'yes',
                    inline: 'yes',
                    close_previous: 'no',
                    popup_css: false // Disable TinyMCE's default popup CSS
                }, {
                    'connector_url': fn_url('elf_connector.images?security_hash=' + _.security_hash),
                    'callback': callback
                });
            },
            entity_encoding : 'raw',
        },

        run: function ($el, params) {
            
            editor.params.toolbar = 'formatselect fontselect fontsizeselect bold italic underline forecolor backcolor | link image | numlist bullist indent outdent | alignleft aligncenter alignright | code';
            if (_.area == 'C') {
                editor.params.toolbar = 'formatselect fontselect fontsizeselect bold italic underline forecolor backcolor | numlist bullist indent outdent | alignleft aligncenter alignright';
            }

            if (typeof($.fn.tinymce) == 'undefined') {
                $.ceEditor('state', 'loading');
                return $.getScript('js/lib/tinymce/jquery.tinymce.min.js', function () {
                    $.ceEditor('state', 'loaded');
                    $el.ceEditor('run', params);
                });
            }

            if (!this.params.setup) {
                this.params.setup = function (editor) {
                    editor.on('init', function () {
                        if ($el.prop('disabled')) {
                            $el.ceEditor('disable', true);
                        }
                    });
                    editor.on('change', function () {
                        $el.ceEditor('changed', editor.getContent());
                    });
                };
            }
            this.params.script_url = _.current_location + '/js/lib/tinymce/tinymce.min.js';

            if (!this.params) {
                this.params = {

                    script_url : _.current_location + '/js/lib/tinymce/tinymce.min.js',
                    plugins: [
                        "advlist autolink lists link image charmap print preview anchor",
                        "searchreplace visualblocks code fullscreen",
                        "insertdatetime media table contextmenu paste textcolor"
                    ],
                    menubar: false,
                    statusbar: true,
                    mode : "textareas",
                    force_p_newlines : true,
                    extended_valid_elements: "i[*],span[*]",
                    forced_root_block : '',
                    media_strict: false,

                    toolbar: 'formatselect fontselect fontsizeselect bold italic underline forecolor backcolor | link image | numlist bullist indent outdent | alignleft aligncenter alignright | code',
                    resize: true,
                    theme : 'modern',
                    language: lang,
                    strict_loading_mode: true,
                    convert_urls: false,
                    remove_script_host: false,
                    body_class: 'wysiwyg-content',
                    file_picker_callback : function(callback, value, meta) {
                        tinyMCE.activeEditor.windowManager.open({
                            file : _.current_location + '/js/lib/elfinder/elfinder.tinymce.html',
                            title: _.tr('file_browser'),
                            width : 600,
                            height : 450,
                            resizable : 'yes',
                            inline : 'yes',
                            close_previous : 'no',
                            popup_css : false // Disable TinyMCE's default popup CSS
                        }, {
                            'connector_url': fn_url('elf_connector.images?security_hash=' + _.security_hash),
                            'callback': callback
                        });
                    },
                    setup: function(ed) {
                        ed.on('init', function(ed) {
                            if (elm.prop('disabled')) {
                                elm.ceEditor('disable', true);
                            }
                        });

                        ed.on('change', function() {
                            elm.ceEditor('changed', ed.getContent());
                        });
                    },
                    entity_encoding : 'raw',
                };

                if (typeof params !== 'undefined' && params[this.editorName]) {
                    $.extend(this.params, params[this.editorName]);
                }
            }

            $el.tinymce(this.params);
        },

        destroy: function ($el) {
            var _this = this;
            tinymce.remove();
            this.is_destroying = true;
            setTimeout(function () {
                // TinyMCE editor disappears by timeout after destroy, even if editor is recovered
                // add delay to track it
                _this.is_destroying = false;
            }, 1);
        },

        recover: function ($el) {
            if (this.is_destroying) {
                setTimeout(function () {
                    $el.ceEditor('run');
                }, 1);
            } else {
                $el.ceEditor('run');
            }
        },

        val: function ($el, value) {
            if (typeof(value) == 'undefined') {
                return $el.val();
            } else {
                $el.val(value);
            }

            return true;
        },

        insert: function (elm, text) {
            tinymce.editors[0].execCommand('mceInsertContent', false, text);
        },

        updateTextFields: function (elm) {
            return true;
        },

        disable: function ($el, value) {
            var state = (value == true) ? 'Off' : 'On';
            $('.mce-toolbar-grp').toggle();
            tinyMCE.editors[0].getBody().setAttribute('contenteditable', !value);
            $el.prop('disabled', value);
        }
    };

    $.ceEditor('handlers', editor);
}(Tygh, Tygh.$));
