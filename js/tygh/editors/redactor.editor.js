/* editior-description:text_redactor */
(function(_, $) {

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
        if ( $( event.target ).closest( ".ui-draggable" ).length ) {
          return true;
        }

        // address interaction issues with iframe based drop downs in IE
        if ( $( event.target ).closest( ".cke" ).length ) {
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

    var methods = {
        _getEditor: function(elm) {
            var obj = $('#' + elm.prop('id'));
            if (obj.data('redactor')) {
                return obj;
            }

            return false;
        }
    };

    $.ceEditor('handlers', {

        editorName: 'redactor',
        params: null,
        elms: [],

        run: function(elm, params) {

            var support_langs = ['ar', 'az', 'ba', 'bg', 'by', 'ca', 'cs', 'da', 'de', 'el', 'en' , 'eo', 'es', 'fa', 'fi', 'fr', 'he', 'hr', 'hu', 'id', 'it', 'ja', 'ko', 'lt', 'lv', 'mk', 'nl', 'no', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sq', 'sr', 'sv', 'th', 'tr', 'ua', 'vi', 'zh'];
            var lang_map = {
                'no': 'no_NB',
                'pt': 'pt_pt',
                'sr': 'sr-cir',
                'zh': 'zh_tw'
            };

            var lang_code = fn_get_listed_lang(support_langs);
            if (lang_code in lang_map) {
                lang_code = lang_map[lang_code];
            }

            if (typeof($.fn.redactor) == 'undefined') {
                $.ceEditor('state', 'loading');
                $.loadCss(['js/lib/redactor/redactor.css']);
                // Load elFinder

                $.loadCss(['js/lib/elfinder/css/elfinder.min.css']);
                $.loadCss(['js/lib/elfinder/css/theme.css']);

                $.getScript('js/lib/elfinder/js/elfinder.min.js');
                $.getScript('js/lib/redactor/plugins/fontcolor/fontcolor.js');
                $.getScript('js/lib/redactor/plugins/table/table.js');
                $.getScript('js/lib/redactor/plugins/video/video.js');
                $.getScript('js/lib/redactor/plugins/imageupload/imageupload.js');
                $.getScript('js/lib/redactor/redactor.min.js', function() {
                    if (lang_code != 'en') {
                        $.getScript('js/lib/redactor/lang/' + lang_code + '.js', function() {
                            callback();
                        });
                    } else {
                        callback();
                    }
                });

                var callback = function() {
                    $.ceEditor('state', 'loaded');
                    elm.ceEditor('run', params);
                };

                return true;
            }

            if (!this.params) {
                this.params = {
                    lang: lang_code

                };
            }

            if (typeof params !== 'undefined' && params[this.editorName]) {
                $.extend(this.params, params[this.editorName]);
            }

            this.params.initCallback = function() {
                $('.redactor-toolbar-tooltip').each(function() {
                    $(this).css('z-index', 50001);
                });
            }

            this.params.blurCallback = function() {
                if (elm.hasClass('redactor-focus')) {
                    elm.redactor('selection.restore');    
                    elm.redactor('selection.save');       
                    elm.removeClass('redactor-focus');
                }
                
            }

            this.params.focusCallback = function() {
                elm.addClass('redactor-focus');    
            }

            this.params.modalOpenedCallback = function() {
                $('#redactor-modal-overlay, #redactor-modal-box, #redactor-modal, .redactor-dropdown').each(function() {
                    $(this).css('z-index', 50001, 'important');
                });
            };

            this.params.dropdownShowCallback = function() {
                $('#redactor-modal-overlay, #redactor-modal-box, #redactor-modal, .redactor-dropdown').each(function() {
                    $(this).css('z-index', 50001, 'important');
                });
            };

            this.params.buttonSource = true;
            this.params.plugins = ['fontcolor', 'table'];
            if (_.area == 'A' || _.live_editor_mode == true) {
                this.params.plugins.push('imageupload', 'video');
            }
            this.params.buttons = ['html', 'formatting', 'bold', 'italic', 'deleted', 'unorderedlist', 'orderedlist',
                                   'outdent', 'indent', 'image', 'video' ,'table', 'link', 'alignment', 'horizontalrule'];
            this.params.replaceDivs = false;
            this.params.changeCallback = function(html) {
                elm.ceEditor('changed', html);
            };

            // Launch Redactor
            elm.redactor(this.params);

            if (elm.prop('disabled')) {
                elm.ceEditor('disable', true);
            }

            this.elms.push(elm.get(0));
            return true;
        },

        destroy: function(elm) {
            var ed = methods._getEditor(elm);
            if (ed) {
                ed.redactor('core.destroy');
            }
        },

        recover: function(elm) {
            if ($.inArray(elm.get(0), this.elms) !== -1) {
                $.ceEditor('run', elm);
            }
        },

        val: function(elm, value) {
            var ed = methods._getEditor(elm);
            if (!ed) {
                return false;
            }

            if (typeof(value) == 'undefined') {
                return ed.redactor('code.get');
            } else {
                ed.redactor('code.set', value);
            }
            return true;
        },

        updateTextFields: function(elm) {
            return true;
        },
        
        insert: function(elm, text) {
            var ed = methods._getEditor(elm);
            
            if (ed) {
                ed.redactor('selection.restore');
                ed.redactor('insert.text', text);
            }
        },

        disable: function(elm, value) {
            var ed = methods._getEditor(elm);
            if (ed) {
                var obj = ed.redactor('core.getBox');
                if (value == true) {
                    if (!$(obj).parent().hasClass('disable-overlay-wrap')) {
                        $(obj).wrap("<div class='disable-overlay-wrap wysiwyg-overlay'></div>");
                        $(obj).before("<div id='" + elm.prop('id') + "_overlay' class='disable-overlay'></div>");
                        elm.prop('disabled', true);
                    }
                } else {
                    $(obj).unwrap();
                    $('#' + elm.prop('id') + '_overlay').remove();
                    elm.prop('disabled', false);
                }
            }
        }
    });
}(Tygh, Tygh.$));
