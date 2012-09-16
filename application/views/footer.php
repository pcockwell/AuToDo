
<script src="<?php echo base_url(); ?>assets/js/jquery-1.7.2.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/bootstrap.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jquery-ui-1.8.21.custom.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jquery-ui-timepicker-addon.js"></script>
<script src="<?php echo base_url(); ?>assets/js/app.js"></script>
<script>
	
	/**
	 * APP
	 * Top-level namespace for application code.
	 */
	var APP = APP || {};
	
	<?php if ( isset($task_list) ): ?>
		APP.tasks = $.parseJSON( <?php echo "'" . json_encode($task_list) . "'"; ?> );
	<?php endif ?>

	<?php if ( isset($conflicts) ): ?>
	APP.conflicts = $.parseJSON( <?php echo "'" . json_encode($conflicts) . "'"; ?> );
	<?php endif ?>
</script>

</body>
</html>