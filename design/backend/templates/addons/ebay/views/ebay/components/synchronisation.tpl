{assign var="return_current_url" value=$config.current_url|escape:url}
{btn id='ebay_synchronization_link' type="text" text='' class="cm-ajax cm-comet" href="ebay.synchronization?site_id=`$site_id`&category_id=`$category_id`&redirect_url=`$return_current_url`" method="POST"}
<script type="application/javascript">
    (function(_, $) {
        $(document).ready(function() {
            $('#ebay_synchronization_link').trigger('click');
        });
    }(Tygh, Tygh.$));
</script>