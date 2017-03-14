(function(_, $) {
    $(document).ready(function(){
        $(_.doc).on('click', '.cm-news-subscribe', function(e) {
            var elms = $(this).parents('.subscription-container').find('.cm-news-subscribe');
            var params = '';
            var all_mailing_lists = '';

            if (elms.length > 0) {
                elms.each(function(){
                    if ($(this).prop('name').length > 0) {
                        if ($(this).prop('checked')) {
                            params += $(this).prop('name') + '=' + $(this).val() + '&';
                        } else {
                            all_mailing_lists += 'all_mailing_lists' + '=' + $(this).val() + '&';
                        }
                    }
                });
            }

            if (!params) {
                params = 'mailing_lists=';
            }

            if (!all_mailing_lists) {
                params += '&all_mailing_lists=';
            } else {
                all_mailing_lists = '&' + all_mailing_lists;
                params += all_mailing_lists;
            }

            $.ceAjax('request', fn_url('checkout.subscribe_customer?' + params), {method: 'post', result_ids: 'subsciption*'});
        });
    });
}(Tygh, Tygh.$));
