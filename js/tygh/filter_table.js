(function(_, $) {

    function globalHandlers()
    {
        // Add new selector to search text inside matched elements
        $.extend($.expr[':'], {
            'containsi': function(elem, i, match, array) {
                var haystack = (elem.textContent || elem.innerText || '').toLowerCase();
                var needle = (match[3] || '').toLowerCase().split(' ');

                for (var k = 0; k < needle.length; k++) {
                    if (haystack.indexOf(needle[k]) != -1) {
                        return true;
                    }
                }

                return false;
            }
        });

        // Re-init search after ajax request
        $.ceEvent('on', 'ce.commoninit', function(context) {
            context.find('.cm-filter-table').ceFilterTable();
        });
    }

    (function($) {

        function setHandlers(container)
        {
            var data = container.data('ceFilterTable'),
                input_elm = data.input_elm,
                clear_elm = data.clear_elm;

            // Clear input
            clear_elm.on('click', function() {
                input_elm.val('').trigger('input');
                clear_elm.addClass('hidden');
            });

            // Perform search and show/hide clear button
            input_elm.on('keyup input', function() {
                filter(container);
            });
        }

        function showItems(container, items, empty_elm)
        {
            var data = {
                items: items,
                empty_elm: empty_elm
            };

            $.ceEvent('trigger', 'ce.filter_table_show_items', [container, data]);

            data.items.show();

            if (data.items.length === 0) {
                data.empty_elm.removeClass('hidden');
            } else {
                data.empty_elm.addClass('hidden');
            }
        }

        function filter(container)
        {
            var data = container.data('ceFilterTable');

            if (typeof data == 'undefined') {
                return;
            }

            var
                input_elm = data.input_elm,
                clear_elm = data.clear_elm,
                empty_elm = data.empty_elm;

            var found_items;
            var items = container.is('table') ? container.find('tr') : container.find('li');

            items.hide();

            if (input_elm.val() === '') {
                showItems(container, items, empty_elm);
                return;
            }

            found_items = items.filter(":containsi('" + input_elm.val() + "')");
            showItems(container, found_items, empty_elm);

            if (input_elm.val().length > 0) {
                clear_elm.removeClass('hidden');
            } else {
                clear_elm.addClass('hidden');
            }
        }

        var methods = {
            init: function(params) {
                return this.each(function() {
                    var self = $(this);

                    self.data('ceFilterTable', {
                        input_elm: $('#' + self.data('caInputId')),
                        clear_elm: $('#' + self.data('caClearId')),
                        empty_elm: $('#' + self.data('caEmptyId'))
                    });

                    setHandlers(self);
                });
            },
            filter: function() {
                return this.each(function() {
                    filter($(this));
                });
            }
        };

        $.fn.ceFilterTable = function(method) {
            if (methods[method]) {
                return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
            } else if (typeof method === 'object' || !method) {
                return methods.init.apply(this, arguments);
            } else {
                $.error('ty.filterTable: method ' + method + ' does not exist');
            }
        };
    })($);

    $(document).ready(function() {
        globalHandlers();
        $('.cm-filter-table').ceFilterTable();
    });

}(Tygh, Tygh.$));
