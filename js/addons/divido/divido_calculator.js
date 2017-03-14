(function(_, $) {

    var dividoDeposit = function(){

        $('input[name="divido_deposit"]').on('change', function() {
            selectDividoData();
        });
    };

    var selectDividoData = function(){

        var data = {
            finance_code: $( "select[name='divido_finance'] option:selected" ).val(),
            deposit: $('span[data-divido-deposit]').text().slice(1)
        };

        $('input[name="payment_info[finance_code]"]').val(data.finance_code);
        $('input[name="payment_info[deposit_amount]"]').val(data.deposit);
    };

    var dividoData = function(){

        selectDividoData();
        dividoDeposit();

        $("select[name='divido_finance']").on('change', function() {
            selectDividoData();
            dividoDeposit();
        });
    };

    var dividoCalculator = function(calculator){
        divido_calculator(calculator); // this function is from divido library
        dividoData();
        $('#divido-checkout').removeClass('hidden');
    };

    $(document).ready(function() {

        var calculators = document.querySelectorAll('[data-divido-calculator]');

        Array.prototype.forEach.call(calculators, function(calculator, i) {
            if (typeof divido_calculator == 'undefined') {
                var apiKey = $('#divido-checkout').data('divido-api-key');
                $.getScript('//cdn.divido.com/calculator/' + apiKey + '.js',  function () {
                    dividoCalculator(calculator);
                });
            } else {
                dividoCalculator(calculator);
            }
        });
    });

}(Tygh, Tygh.$));