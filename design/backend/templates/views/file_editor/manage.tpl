{capture name="mainbox"}

    {script src="js/lib/elfinder/js/elfinder.min.js"}

    {if $smarty.const.CART_LANGUAGE != 'en'}
    {script src="js/lib/elfinder/js/i18n/elfinder.`$smarty.const.CART_LANGUAGE`.js"}
    {/if}

    <script type="text/javascript">
    (function(_, $) {

        $.loadCss(['js/lib/elfinder/css/elfinder.min.css']);
        $.loadCss(['js/lib/elfinder/css/theme.css']);

        $(document).ready(function() {

            var w = $.getWindowSizes();
            $('#elfinder').elfinder({
                url : fn_url('elf_connector.manage?start_path={$smarty.request.path}&security_hash=' + _.security_hash),
                rememberLastDir: true,
                useBrowserHistory: true,
                resizable: false,
                lang: _.cart_language,
                height: w.view_height - 170,
                uiOptions: {
                    toolbar : [
                        ['back', 'forward'],
                        ['mkdir', 'mkfile', 'upload'],
                        ['download'],
                        ['info'],
                        ['quicklook'],
                        ['copy', 'cut', 'paste'],
                        ['rm', 'rename'],
                        ['edit'],
                        ['extract', 'archive'],
                        ['search'],
                        ['view']
                    ]
                },
                requestType: 'post'
            });
        });
    }(Tygh, Tygh.$))
    </script>

    <div id="elfinder"></div>

{/capture}
{include file="common/mainbox.tpl" content=$smarty.capture.mainbox title=__("file_editor") buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar sidebar_position="left"}
