<?php require_once(__DIR__ .'/../_header.php'); ?>

<fieldset>
<h1>Manage users</h1>
	<?php  				
		// Show reset password message if exist
		if (isset($reset_message))
			echo $reset_message;
		
		// Show error
		echo validation_errors();

		
		

		/*						

		$templateTable = array(
			'table_open' 			=>    '<table class="table table-bordered tablesorter table-striped" id = "asda">',
			'heading_cell_start'    => 	  '<th class="text-left field-sorting">',
       		'heading_cell_end'      => 	  '</th>',
       		'cell_start'            => 	  '<td><div class="text-left">',
        	'cell_end'              =>    '</div></td>',
			'table_close'  			=> 	  '</table>'
			);
		
		$this->table->set_template($templateTable);
		$this->table->set_heading('', 'Username', 'Email', 'Role', 'Banned', 'Last IP', 'Last login', 'Created'); */

		

		$count = 0;
		$arrayOfRows = array();
		foreach ($users as $user) 
		{
			$banned = ($user->banned == 1) ? 'Yes' : 'No';
			
			/*$this->table->add_row(
				form_checkbox('checkbox_'.$user->id, $user->id),
				$user->username, 
				$user->email, 
				$user->role_name, 			
				$banned, 
				$user->last_ip,
				date('Y-m-d', strtotime($user->last_login)), 
				date('Y-m-d', strtotime($user->created)));*/

			$arrayOfRows[$count]['checkBox'] 	= form_checkbox('checkbox_'.$user->id, $user->id);
			$arrayOfRows[$count]['userName'] 	= $user->username;
			$arrayOfRows[$count]['eMail']	 	= $user->email;
			$arrayOfRows[$count]['roleName'] 	= $user->role_name;
			$arrayOfRows[$count]['bannedLabel'] = $banned;
			$arrayOfRows[$count]['lastIp'] 		= $user->last_ip;
			$arrayOfRows[$count]['lastLogin'] 	= date('Y-m-d', strtotime($user->last_login));
			$arrayOfRows[$count]['createDate'] 	= date('Y-m-d', strtotime($user->created));
			$count++;
		}


		
		echo form_open($this->uri->uri_string());
		//echo '<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/js/jquery-1.11.1.min.js"></script>';
		
		echo '<link type="text/css" rel="stylesheet" href="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/css/bootstrap.min_edit.css">';
		echo '<link type="text/css" rel="stylesheet" href="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/css/style.css">';

		echo '<div class="twitter-bootstrap">';
		echo '<div id="main-table-box"> ';

		echo form_submit('ban', 'Ban user', "class='btn btn-primary'");
		echo form_submit('unban', 'Unban user', "class='btn btn-primary'");
		echo form_submit('reset_pass', 'Reset password', "class='btn btn-primary'");
		
		echo '<hr/>';

		echo '<div id="ajax_list">';
		echo '<div class>';
		echo '
		<table class="table table-bordered table-striped tablesorter" id="asda">
		<thead>
		<tr>
		<th class = "no-sorter"></th>
		<th class="text-left field-sorting no-sorter" onclick = "activateIcon(0)" id = "headerUsername">Username</th>
		<th class="text-left field-sorting  no-sorter " onclick = "activateIcon(1)" id = "headerEmail">Email</th>
		<th class="text-left field-sorting  no-sorter" onclick = "activateIcon(2)" id = "headerRole">Role</th>
		<th class="text-left field-sorting  no-sorter" onclick = "activateIcon(3)" id = "headerBanned">Banned</th>
		<th class="text-left field-sorting  no-sorter" onclick = "activateIcon(4)" id = "headerLastIP">Last IP</th>
		<th class="text-left field-sorting  no-sorter" onclick = "activateIcon(5)" id = "headerLastLogin">Last Login</th>
		<th class="text-left field-sorting  no-sorter" onclick = "activateIcon(6)" id = "headerCreated">Created</th>
		</tr>
		</thead>


		<tbody>';

		$countData = 0;
		while($countData < count($arrayOfRows)){
			echo '<tr>';
			echo '<td> <div class="text-left">'.$arrayOfRows[$countData]['checkBox'].'</div></td>';
			echo '<td> <div class="text-left">'.$arrayOfRows[$countData]['userName'].'</div></td>';
			echo '<td> <div class="text-left">'.$arrayOfRows[$countData]['eMail'].'</div></td>';
			echo '<td> <div class="text-left">'.$arrayOfRows[$countData]['roleName'].'</div></td>';
			echo '<td> <div class="text-left">'.$arrayOfRows[$countData]['bannedLabel'].'</div></td>';
			echo '<td> <div class="text-left">'.$arrayOfRows[$countData]['lastIp'].'</div></td>';
			echo '<td> <div class="text-left">'.$arrayOfRows[$countData]['lastLogin'].'</div></td>';
			echo '<td> <div class="text-left">'.$arrayOfRows[$countData]['createDate'].'</div></td>';
			echo '</tr>';
			$countData++;
		}



		echo '</tbody>';
		echo '</table>';
		

		//echo $this->table->generate();

		echo '</div>'; 
		echo '</div>'; 
		echo '</div>'; 
		echo '</div>'; 

		
		
		
		echo form_close();
		
		//echo $pagination;
			
	?>
</fieldset>


<script type="text/javascript">
	var arrayHeaders = ["headerUsername","headerEmail","headerRole", "headerBanned", "headerLastIP", "headerLastLogin", "headerCreated"];
	function activateIcon(idNumber){
	 	var listofClasses = document.getElementById(arrayHeaders[idNumber]).classList;
	 	var direction = "upward"

	 	if((listofClasses).contains("asc")){
	 		listofClasses.add("desc");
	 		direction = "downward";
	 		listofClasses.remove("asc");
	 	}else if((listofClasses).contains("desc")) {
	 		listofClasses.add("asc");
	 		direction = "upward"
	 		listofClasses.remove("desc");
	 	}else{
	 		listofClasses.add("asc");
	 		direction = "upward"
	 	}

	 	var counterForArray = 0;
	 	while(counterForArray<arrayHeaders.length){
	 		if(counterForArray == idNumber){
	 			counterForArray++;
	 			continue;	
	 		}else{
	 			listofClasses = document.getElementById(arrayHeaders[counterForArray]).classList;
	 			listofClasses.remove("desc");
	 			listofClasses.remove("asc");
	 			counterForArray++;
	 		}
	 	}

	 	var rowData = [];
	 	var counterForRows = 0;
	 	var counterForColumns = 0;

	 	var dataInput;


	 	while(counterForRows < document.getElementById("asda").rows.length - 1){
	 		counterForColumns = 0;
	 		rowData[counterForRows] =  [];

	 		while(counterForColumns < document.getElementById("asda").rows[counterForRows].cells.length){
	 			rowData[counterForRows][counterForColumns] = document.getElementById("asda").rows[counterForRows + 1].cells[counterForColumns].innerHTML;
	 			dataInput = document.getElementById("asda").rows[counterForRows + 1].cells[counterForColumns].innerHTML;
	 			counterForColumns++;
	 		}
	 		counterForRows++;
	 	}

		
		rowData = sorter(rowData, idNumber + 1);

		if(direction == "downward"){
			rowData.reverse();
		}


		var counterForRows = 0;
	 	var counterForColumns = 0;

	 	while(counterForRows < document.getElementById("asda").rows.length - 1){
	 		counterForColumns = 0;
	 		while(counterForColumns < document.getElementById("asda").rows[counterForRows].cells.length){
	 			document.getElementById("asda").rows[counterForRows + 1].cells[counterForColumns].innerHTML = rowData[counterForRows][counterForColumns];
	 			counterForColumns++;
	 		}
	 		counterForRows++;
	 	}
	}

	function sorter(rowData, idNumber){
	  var swapData;
	  var swapCount = 0;

	  do{
	      for (var i = 1, swapDataCount = 0; i < rowData.length; i++){
	          if(rowData[i - 1][idNumber]>rowData[i][idNumber]){
	              swapData = rowData[i - 1];
	              rowData[i - 1] = rowData[i];
	              rowData[i] = swapData; 
	              swapDataCount +=1;
	          }
	      }
	  }while(swapDataCount>0 ); 
  	return rowData; 
}

</script>

<link type="text/css" rel="stylesheet" href="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/css/bootstrap.min_edit.css">
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/js/jquery-1.11.1.min.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/js/jquery-ui/jquery-ui-1.9.2.custom.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/js/common/lazyload-min.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/js/common/list.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/js/libs/bootstrap/application.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/js/libs/modernizr/modernizr-2.6.1.custom.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/js/libs/tablesorter/jquery.tablesorter.min.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/js/cookies.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/js/jquery.form.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/js/jquery_plugins/jquery.numeric.min.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/js/libs/print-element/jquery.printElement.min.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/js/jquery_plugins/jquery.fancybox-1.3.4.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/js/jquery_plugins/jquery.easing-1.3.pack.js"></script>
<script src="http://52.10.213.50/pccwsolutions2016/admin/assets/grocery_crud/themes/twitter-bootstrap/js/jquery.functions.js"></script>



<?php require_once(__DIR__ .'/../_footer.php'); ?>