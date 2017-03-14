/* editior-description:do_not_use */

(function(_, $) {
    $.ceEditor('handlers', {
        
        run: function(elm) {

            elm.change(function() {
                elm.ceEditor('changed', elm.val());
            });
            return true;
        },

        destroy: function(elm) {
            return true;
        },

        recover: function(elm) {
            return true;
        },

        updateTextFields: function(elm) {
            return true;
        },
               
        val: function(elm, value) {
            if ($.type(value) == 'undefined') {
                return elm.val();
            }
            elm.val(value);
        },

        disable: function(elm, value) {
            return true;
        },

        insert: function(elm, value) {
            return true;
        }
    });
}(Tygh, Tygh.$));
