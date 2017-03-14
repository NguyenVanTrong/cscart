(function(_, $) {

    $(document).ready(function(){
        $(_.doc).on('change', '.cm-om-totals input:visible, .cm-om-totals select:visible, .cm-om-totals textarea:visible', function(){
            var is_changed = $('.cm-om-totals').formIsChanged();
            $('.cm-om-totals-price').toggleBy(is_changed);
            $('.cm-om-totals-recalculate').toggleBy(!is_changed);
        });

        $(_.doc).on('keypress', 'form[name=om_cart_form] input[type=text]', function(e) {
            if(e.keyCode == 13) {
                $(this).blur();
                return false;
            }
        });
    });

}(Tygh, Tygh.$));
