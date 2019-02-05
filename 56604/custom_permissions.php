<?php require_once(__DIR__ .'/../_header.php'); ?>

<script type="text/javascript">
	$(function() {
    $('#role').change(function() {
        this.form.submit();
    });
});
</script>

<fieldset>
<h1>Manage Custom Permissions</h1>
	<?php
		echo '<b>Here is an example how to use custom permissions</b><br/><br/>';
		
		// Build drop down menu
		foreach ($roles as $role)
		{
			$options[$role->id] = $role->name;
		}

		// Change allowed uri to string to be inserted in text area
		if ( ! empty($allowed_uri))
		{
			$allowed_uri = implode("\n", $allowed_uri);
		}
		
		if (empty($edit))
		{
			$edit = FALSE;
		}
			
		if (empty($delete))
		{
			$delete = FALSE;
		}
		
		// Build form
		echo form_open($this->uri->uri_string());
		
		echo form_label('Role', 'role_name_label');
		$role = 'id="role"';
		$role_label=$_POST['role'];
		echo form_dropdown('role', $options, $role_label, $role); 
		// echo form_submit('show', 'Show permissions'); 
		
		echo form_label('', 'uri_label');
				
		echo '<hr/>';


		echo form_checkbox('visitPccwSolution', '1', $visitPccwSolution);
		echo form_label('Allow PCCW Solution Tab', 'visitPccwSolution_label');
		echo '<br/>';

		echo form_checkbox('visitHaPortalApp', '1', $visitHaPortalApp);
		echo form_label('Allow HA Portal App Tab', 'visitHaPortalApp_label');
		echo '<br/>';

		echo form_checkbox('visitDev', '1', $visitDev);
		echo form_label('Allow Dev Tab', 'visitDev_label');
		echo '<br/>';

		echo form_checkbox('visitRecordControl', '1', $visitRecordControl);
		echo form_label('Allow Record Control Tab', 'visitRecordControl_label');
		echo '<br/>';

		echo form_checkbox('visitDigitalAsset', '1', $visitDigitalAsset);
		echo form_label('Allow Digital Asset Tab', 'visitDigitalAsset_label');
		echo '<br/>';

		echo form_checkbox('visitAdministrator', '1', $visitAdministrator);
		echo form_label('Allow Administrator Tab', 'visitAdministrator_label');
		echo '<br/>';

		echo form_checkbox('edit', '1', $edit);
		echo form_label('Allow Edit', 'edit_label');
		echo '<br/>';
		
		echo form_checkbox('delete', '1', $delete);
		echo form_label('Allow Delete', 'delete_label');
		echo '<br/>';

		echo form_checkbox('approveAndReject', '1', $approveAndReject);
		echo form_label('Allow Approve and Reject', 'approveAndReject_label');
		echo '<br/>';
					
		echo '<br/>';
		echo form_submit('save', 'Save Permissions');
		
		echo '<br/>';
		
		echo 'Open '.anchor('auth/custom_permissions/').' to see the result, try to login using user that you have changed.<br/>';
		echo 'If you change your own role, you need to relogin to see the result changes.';
		
		echo form_close();
			
	?>
</fieldset>
<?php require_once(__DIR__ .'/../_footer.php'); ?>