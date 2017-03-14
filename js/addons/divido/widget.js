(function(_, $) {

    $(document).ready(function() {

        $('[data-divido-widget]').each(function(i, widget) {
            if (typeof divido_calculator == 'undefined') {
                var apiKey = $('[data-divido-api-key]').data('divido-api-key');
                $.getScript('//cdn.divido.com/calculator/' + apiKey + '.js',  function () {
                    divido_widget(widget);
                });
            } else {
                divido_widget(widget);
            }
        });

        // required to prevent scrolling to the window top
        // when clicking 'or X per month' link in the calculator widget
        $('.ty-divido__price').on('click', '.divido-widget-launcher a', function(e) {
            return false;
        });
    });

}(Tygh, Tygh.$));