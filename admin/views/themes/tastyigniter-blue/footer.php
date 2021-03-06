<?php
		$locations = array();
		if ($this->user->islogged()) {
			$no_sidenav = '';
			//$this->load->model('Locations_model');
			$results = array(); //$this->Locations_model->getLocations();

			foreach ($results as $result) {
				$locations[] = array(
					'location_id'	=>	$result['location_id'],
					'location_name'	=>	$result['location_name'],
				);
			}
		}
?>
</div>
<div id="footer" class="<?php echo ($this->user->islogged()) ? '' : 'wrap-none'; ?>">
	<div class="row navbar-footer">
		<div class="col-sm-8">
			<p class="text-copyright">&copy; <?php echo date('Y'); ?> TastyIgniter. All Rights Reserved <?php echo config_item('ti_version'); ?></p>
		</div>
		<div id="profiler" class="col-sm-4"></div>
		<?php if ($locations) { ?>
			<div class="col-sm-3 navbar-locations">
				<form role="form" class="navbar-form navbar-left" role="form">
					<div class="form-group">
						<select name="location" class="form-control">
						<?php foreach ($locations as $location) { ?>
							<?php if ($this->user->getLocationId() === $location['location_id']) { ?>
								<option value="<?php echo $location['location_id']; ?>" selected="selected"><?php echo $location['location_name']; ?></option>
							<?php } else { ?>
								<option value="<?php echo $location['location_id']; ?>"><?php echo $location['location_name']; ?></option>
							<?php } ?>
						<?php } ?>
						</select>
					</div>
				</form>
			</div>
		<?php } ?>
	</div>
</div>
</div>
<script type="text/javascript">
$(document).ready(function() {
	if ($('#codeigniter_profiler').length) {
		$('#footer #profiler').append('<a class="btn btn-default btn-profiler"><i class="fa fa-bug"></i></a>');
		$('.btn-profiler').on('click', function(){
			if($('#codeigniter_profiler').is(':visible')) {
				$('#codeigniter_profiler').fadeOut();
			} else {
				$('#codeigniter_profiler').fadeIn();
			}
		});

		 $('#codeigniter_profiler').fadeOut();
	}

	//Delete Confirmation Box
	$('#list-form').submit(function(){
		//if ($('input[name=\'delete\']').attr("checked") == "checked") {
			if (!confirm('This cannot be undone! Are you sure you want to do this?')) {
				return false;
			}
		//}
	});

	//Uninstall Confirmation Box
	$('a').click(function(){
		if ($(this).attr('href') != null && $(this).attr('href').indexOf('uninstall', 1) != -1) {
			if (!confirm('This cannot be undone! Are you sure you want to do this?')) {
				return false;
			}
		}
	});
	
	if (document.location.toString().toLowerCase().indexOf(active_menu, 1) != -1) {
		//$('.' + active_menu).parents('.collapse').addClass('in');
		$('#side-menu .' + active_menu).addClass('active');
		$('#side-menu .' + active_menu).parents('.collapse').parent().addClass('active');
		$('#side-menu .' + active_menu).parents('.collapse').collapse('show');
		$('#side-menu .' + active_menu).parents('.collapse').collapse('show');
	}

	if (window.location.hash) {
		var hash = window.location.hash.substring(1); //Puts hash in variable, and removes the # character
		$('html,body').animate({scrollTop: $('#wrapper').offset().top - 45}, 800);
		$('#nav-tabs a[href="#'+hash+'"]').tab('show');
	}

	$(document).on('change', '.btn-group-toggle input[type="radio"], .btn-group input[type="radio"]', function() {
		var btn = $(this).parent();
		var parent = btn.parent();

		if (btn.attr('data-btn')) {
			parent.find('.btn').removeClass('btn-primary btn-success btn-info btn-warning btn-danger');
			btn.addClass(btn.attr('data-btn'));
		} else {
			btn.addClass('btn-success');
		}
	});

	$('.btn-group-toggle .active input[type="radio"], .btn-group .active input[type="radio"]').trigger('change');

	/*if ($('.form-group .text-danger').length > 0) {
		$('.form-group .text-danger').parents('.form-group').addClass('has-error');
	}*/
});

function saveClose() {
	$('#edit-form').append('<input type="hidden" name="save_close" value="1" />');
	$('#edit-form').submit();
}
</script>
</body>
</html>