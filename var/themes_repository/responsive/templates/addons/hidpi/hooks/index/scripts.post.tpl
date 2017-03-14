{script src="js/addons/hidpi/retina.js"}
<script type="text/javascript">
    Retina.configure({
        image_host: '{$hidpi_image_host|escape:javascript}',
        check_mime_type: true,
	 	retinaImgTagSelector: 'img',
		retinaImgFilterFunc: undefined
    });
</script>
{script src="js/addons/hidpi/func.js"}