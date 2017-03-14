<?php
    if (!defined('INSTALLER_INITED')) { die('Access denied'); }

    if ($notification['type'] == 'E') {
        $class = ' alert-error';
    } elseif ($notification['type'] = 'W') {
        $class = ' alert-block';
    } else {
        $class = '';
    }
?>
<div class="alert <?php echo $class; ?>">
	<?php if (!$notification['non_closable']): ?>
    	<button type="button" class="close" data-dismiss="alert">&times;</button>
    <?php endif; ?>
    <?php echo $notification['message']; ?>
</div>
