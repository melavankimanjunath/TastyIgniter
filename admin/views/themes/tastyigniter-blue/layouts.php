<?php echo get_header(); ?>
<div class="row content">
	<div class="col-md-12">
		<div class="panel panel-default panel-table">
			<div class="panel-heading">
				<h3 class="panel-title">Layout List</h3>
			</div>
			<form role="form" id="list-form" accept-charset="utf-8" method="post" action="<?php echo current_url(); ?>">
				<div class="table-responsive">
					<table border="0" class="table table-striped table-border">
						<thead>
							<tr>
								<th class="action"><input type="checkbox" onclick="$('input[name*=\'delete\']').prop('checked', this.checked);"></th>
								<th>Layout</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php if ($layouts) { ?>
							<?php foreach ($layouts as $layout) { ?>
							<tr>
								<td class="action"><input type="checkbox" value="<?php echo $layout['layout_id']; ?>" name="delete[]" />&nbsp;&nbsp;&nbsp;
									<a class="btn btn-edit" title="Edit" href="<?php echo $layout['edit']; ?>"><i class="fa fa-pencil"></i></a></td>
								<td><?php echo $layout['name']; ?></td>
								<td></td>
							</tr>
							<?php } ?>
							<?php } else { ?>
							<tr>
								<td colspan="3"><?php echo $text_empty; ?></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</form>
		</div>
	</div>
</div>
<?php echo get_footer(); ?>