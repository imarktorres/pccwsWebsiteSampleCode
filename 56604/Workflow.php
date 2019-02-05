<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once '../admin/assets/htmlpurifier/HTMLPurifier.auto.php';


class Workflow extends CI_Controller {
	
	private static $TB_NAME				= 'item';
	private static $TB_NAME_APP			= 'item_app';
  	private static $TB_NAME_APPROVAL	= 'item_approval';
  	private static $TB_NAME_RESTORE		= 'item_versioning'; 
	private static $ResotreTo_NAME		= 'lynx2_product';
	private static $status				= 'waiting';
	private $dropdown_type 			= array('4'=>'Other', '3'=>'Folder', '2'=>'Page', '1'=>'File', '0'=>'Misc');
	private $template_id 			= array('blank'=>'blank', 'about_us'=>'about_us', 'blog'=>'blog', 'career'=>'career', 'career_bga'=>'career_bga', 'career_bgb'=>'career_bgb', 'career_bgc'=>'career_bgc', 'career_home'=>'career_home', 'cloud_computing'=>'cloud_computing', 'cloud_computing_baidu'=>'cloud_computing_baidu', 'contactus'=>'contactus', 'datacentre'=>'datacentre', 'D-Infinitum'=>'D-Infinitum', 'download'=>'download', 'event'=>'event', 'event_1_page'=>'event_1_page', 'event_home'=>'event_home', 'event_home_manual'=>'event_home_manual', 'event_single_page'=>'event_single_page','index'=>'index', 'industries'=>'industries', 'industries_Gov'=>'industries_Gov', 'industries_subFolder'=>'industries_subFolder', 'industries_blank'=>'industries_blank', 'news'=>'news', 'newsroom'=>'newsroom', 'newsroom_l'=>'newsroom_l', 'outsourcing'=>'outsourcing', 'pccw'=>'pccw', 'pccw_l'=>'pccw_l', 'pccw_l_ref'=>'pccw_l_ref', 'public'=>'public', 'software'=>'software', 'trainee_jacky'=>'trainee_jacky', 'sitemap'=>'sitemap', 'terms'=>'terms');
	private $image_url 				= '../uploads/PCCWS/public';
	private $dateString;
	
	public function __construct(){
		parent::__construct();

		date_default_timezone_set('Asia/Hong_Kong');
		
		$this->load->database();
		$this->load->helper('url');
		$this->load->helper('date');
		$this->load->library('grocery_CRUD');
		$this->load->library('session');
		$this->load->library('DX_Auth');
		

		$this->load->model('mWorkflow');
		$this->mWorkflow->setCurrentTable(Workflow::$TB_NAME, Workflow::$TB_NAME_APP, Workflow::$TB_NAME_APPROVAL, Workflow::$TB_NAME_RESTORE);
		
		
		$this->dx_auth->check_uri_permissions(); 
		
		$this->dateString = '%Y-%m-%d %h:%i %a';
		$parent_uuid = @$_REQUEST['parent_uuid'];
		if($parent_uuid)
		  $this->session->set_userdata('parent_uuid', $parent_uuid);

	}
	
	function index(){
		try{
			// <----- basic
			$crud							= new grocery_CRUD();
			$crud->set_theme('twitter-bootstrap');
			$crud->set_table(Workflow::$TB_NAME_APP);
			$crud->set_primary_key('uuid');
			// <----- render
			$output							= $crud->render();
			
			// <----- render
			$output->title					= '<div>Workflow</div>';
			$output->parent_uuid			= $this->uri->segment(4);
			
			$this->load->view('workflowView', $output);
			
		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}
	
	function read() {
		$parent_uuid						= $this->uri->segment(3);
		$output								= $this->mWorkflow->getByParent_uuid($parent_uuid);
		foreach ($output as $key => $value){
			$value->create_date 			= $this->dateFormat($value->create_date);
		}
		echo json_encode($output);
	}

	

	function approve(){
		try{
			$now							= new DateTime();

			$uuid							= $this->uri->segment(4);
			$this->db						= $this->load->database('lynx2_solutions',true);
			$this->getCurrentTable();

			$approval_sql					= 'SELECT * FROM '.Workflow::$TB_NAME_APPROVAL.' WHERE parent_uuid = "'.$uuid.'"';
			$approval_query 				= $this->db->query($approval_sql);
			$approval_data					= $approval_query->result();
			
			

			if($approval_data != null && isset($approval_data)){

				$approval_data				= $approval_data[0];
				$update_arr					= array("action" 	  => "approved",
													"create_date" => $now->getTimestamp()
													);
				$this->db->where('uuid', $approval_data->uuid);
				$approval_result			= $this->db->update(Workflow::$TB_NAME_APPROVAL,$update_arr);

			}else{

				$sql						= 'SELECT * FROM '.Workflow::$TB_NAME.' WHERE uuid = "'.$uuid.'"';
				$query						= $this->db->query($sql);
				$data						= $query->result();
				$data						= $data[0];
				$now						= new DateTime();
				/*
				if (array_key_exists("s8415ac02",$data)){
					unset($data["s8415ac02"]);
				}
				*/				

				$insert_arr					= array("uuid" 		  => $this->uuid->v5($data->title),
													"parent_uuid" => $data->uuid,
													"tb_name" 	  => Workflow::$TB_NAME,
													"title" 	  => $data->title,
													"action" 	  => "approved",
													"data" 		  => json_encode($data),
													"create_date" => $now->getTimestamp()
													);

				$approval_result			= $this->db->insert(Workflow::$TB_NAME_APPROVAL,$insert_arr);
				$approval_data				= (object) $insert_arr;
				
			}


			
			if($approval_result){

				$sql						= 'SELECT * FROM '.Workflow::$TB_NAME.' WHERE uuid = "'.$uuid.'"';
				$query						= $this->db->query($sql);
				$TB_NAME_data				= $query->result();
				$TB_NAME_data				= $TB_NAME_data[0];
				$insert_arr					= $this->objectToArray($TB_NAME_data);
				$insert_arr['update_date']	= $now->getTimestamp();
				$insert_arr['status']		= "Approved";
				
				
				//$insert_arr['create_date'] = $now->getTimestamp();

				$sql						= 'SELECT * FROM '.Workflow::$TB_NAME_APP.' WHERE uuid = "'.$uuid.'"';
				$query						= $this->db->query($sql);
				$data						= $query->result();
				
				//echo "-----------data--------";
				//$this->print_arr($data);
				//exit();
				
				/*
				if (array_key_exists("s8415ac02",$insert_arr)){
					unset($insert_arr["s8415ac02"]);
				}
				*/
				
				if($data != null && $data != 0){
					$data					= $data[0];
					$data					= $this->objectToArray($data);
					//$this->print_arr($data);
					//exit();

					$this->db->where('uuid', $insert_arr["uuid"]);

					//echo '<br>data<br>';
					//$this->print_arr($data);


					//echo '<br>app_result<br>';
					//$this->print_arr($app_result);
					//exit();

					$app_result				= $this->db->update(Workflow::$TB_NAME_APP, $this->restrictArray(Workflow::$TB_NAME_APP,$insert_arr));

					

					//echo "record exists in table";

				}else{
					$app_result				= $this->db->insert(Workflow::$TB_NAME_APP, $this->restrictArray(Workflow::$TB_NAME_APP,$insert_arr));

					//echo "record does not exist in table";
				}

				$sql = 'SELECT * FROM '.Workflow::$TB_NAME_RESTORE.' WHERE parent_uuid = "'.$uuid.'" ORDER BY create_date desc';
				$query = $this->db->query($sql);
				$checkData 	= $query->result();

				foreach ($checkData as $rowData){
					$newUUID = $rowData->uuid;
					break;
				}

				$update_arr 				= array("action"		=> 'Approved');
				$this->db->where('uuid', $newUUID);
				$this->db->update(Workflow::$TB_NAME_RESTORE,$update_arr);



			}else{
				echo 'The record cannot be updated to '.$this->uri->segment(3);
			}

			if(($approval_result && $app_result) == true) {
				if(strcasecmp($this->uri->segment(3), 'approvalList') == 0){
					redirect(''.$approval_redirect.'/workflow/approvalList', 'refresh');
				}else{
					redirect(''.$approval_redirect.'/?parent_uuid='.$insert_arr["parent_uuid"], 'refresh');
				}
			}elseif($approval_result){
				echo "error in update approval<br>";
			}elseif($app_result){
				echo "error in insert table<br>";
			}


		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}

	function reject(){
		try{
			$now							= new DateTime();
			$uuid							= $this->uri->segment(4);
			$this->db						= $this->load->database('lynx2_solutions',true);
			$this->getCurrentTable();

			$sql = 'SELECT * FROM '.Workflow::$TB_NAME_RESTORE.' WHERE parent_uuid = "'.$uuid.'" AND action <> "Draft" ORDER BY create_date desc';
			$query = $this->db->query($sql);
			$rowcount = $query->num_rows();
			$checkData = $query->result();


			if($checkData != NULL && $rowcount >1){
				$counter = 0;
				foreach ($checkData as $rowData){
					if($counter == 0){
						$formerUUID = $rowData->uuid;
					}else{
						$returnRow = json_decode($rowData->data,true);
						$newAction = $rowData->action;
						$newUUID = $rowData->uuid;
						break;
					}
					$counter++;
				}

				$returnRowObject = (object)$returnRow;

				$update_arr 				= array("action"		=> 'Draft');
				$this->db->where('uuid', $formerUUID);
				$this->db->update(Workflow::$TB_NAME_RESTORE,$update_arr);


				$update_arr 				= array("create_date"	=> $now->getTimestamp());
				$this->db->where('uuid', $newUUID);
				$this->db->update(Workflow::$TB_NAME_RESTORE,$update_arr);

				$update_arr 				= array("action"		=> $newAction,
													"create_date"	=> $now->getTimestamp(),
													"data"			=> json_encode($returnRow),
													"title"			=> $returnRowObject->title);
				$this->db->where('parent_uuid', $uuid);
				$this->db->update(Workflow::$TB_NAME_APPROVAL,$update_arr);


				$update_arr					= array("name" 	 	  	=> $returnRowObject->name,
													"ext" 	  	  	=> $returnRowObject->ext,
													"title" 	  	=> $returnRowObject->title,
													"content" 	  	=> $returnRowObject->content,
													"keyword" 	  	=> $returnRowObject->keyword,
													"description" 	=> $returnRowObject->description,
													"type" 	  	  	=> $returnRowObject->type,
													"site" 		  	=> $returnRowObject->site,
													"template_id" 	=> $returnRowObject->template_id,
													"version" 	  	=> $returnRowObject->version,
													"status" 	  	=> $returnRowObject->status,
													"sequence"    	=> $returnRowObject->sequence,
													"menu" 		  	=> $returnRowObject->menu,
													"popup" 	  	=> $returnRowObject->popup,
													"alias" 	  	=> $returnRowObject->alias,
													"image" 	  	=> $returnRowObject->image,
													"effective_date"=> $returnRowObject->effective_date,
													"expire_date" 	=> $returnRowObject->expire_date,
													"author_id"  	=> $returnRowObject->author_id,
													"create_date" 	=> $now->getTimestamp(),
													"update_date" 	=> $now->getTimestamp(),
													);

				$this->db->where('uuid', $uuid);
				$app_result				= $this->db->update(Workflow::$TB_NAME,$update_arr);

				$sql = 'SELECT * FROM '.Workflow::$TB_NAME.' WHERE uuid = "'.$uuid.'"';
				$query = $this->db->query($sql);
				foreach ($query->result() as $rowData){
					$returnParentUUID = $rowData->parent_uuid;
					break;
				}

				if($app_result == true) {
					if(strcasecmp($this->uri->segment(3), 'approvalList') == 0){
						redirect(''.$approval_redirect.'/workflow/approvalList', 'refresh');
					}else{
						redirect(''.$approval_redirect.'/?parent_uuid='.$returnParentUUID, 'refresh');
					}
				}	
			}else{
				$this->db->where('uuid', $uuid);
		  		$this->db->delete(Workflow::$TB_NAME);

		  		$this->db->where('parent_uuid', $uuid);
		  		$this->db->delete(Workflow::$TB_NAME_APPROVAL);

		  		$update_arr 				= array("action"		=> 'Draft');
				$this->db->where('parent_uuid', $uuid);
				$this->db->update(Workflow::$TB_NAME_RESTORE,$update_arr);



		  		if(strcasecmp($this->uri->segment(3), 'approvalList') == 0){
					redirect(''.$approval_redirect.'/workflow/approvalList', 'refresh');
				}else{
					redirect(''.$approval_redirect.'/?parent_uuid='.$returnParentUUID, 'refresh');
				}
			}
			

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}


    function remove(){
		try{
			$now							= new DateTime();
			$uuid							= $this->uri->segment(3);
			$approval_redirect				= $this->session->userdata('approval_redirect');
			$this->db						= $this->load->database('lynx2_solutions',true);
			$this->getCurrentTable();

			$this->db->where('uuid', $uuid);
  			$app_result				= $this->db->delete(Workflow::$TB_NAME);
  
  			$this->db->where('uuid', $uuid);
  			$app_result				= $this->db->delete(Workflow::$TB_NAME_APP);

  			$this->db->where('parent_uuid', $uuid);
  			$app_result				= $this->db->delete(Workflow::$TB_NAME_RESTORE);

  			$this->db->where('parent_uuid', $uuid);
  			$app_result				= $this->db->delete(Workflow::$TB_NAME_APPROVAL);


			/*

			$approval_sql					= 'SELECT * FROM '.Workflow::$TB_NAME_APPROVAL.' WHERE parent_uuid = "'.$uuid.'"';
			$approval_query 				= $this->db->query($approval_sql);
			$approval_data					= $approval_query->result();
			
						


			if($approval_data != null && isset($approval_data)){
				$approval_data				= $approval_data[0];
				$update_arr					= array("action" 	  => "approved",
													"create_date" => $now->getTimestamp()
													);
				$this->db->where('uuid', $approval_data->uuid);
				$approval_result			= $this->db->update(Workflow::$TB_NAME_APPROVAL,$update_arr);
			}else{
				$sql						= 'SELECT * FROM '.Workflow::$TB_NAME.' WHERE uuid = "'.$uuid.'"';
				$query						= $this->db->query($sql);
				$data						= $query->result();
				$data						= $data[0];

				$now						= new DateTime();
				/*
				if (array_key_exists("s8415ac02",$data)){
					unset($data["s8415ac02"]);
				}
								

				$insert_arr					= array("uuid" 		  => $this->uuid->v5($data->title),
													"parent_uuid" => $data->uuid,
													"tb_name" 	  => Workflow::$TB_NAME,
													"title" 	  => $data->title,
													"action" 	  => "approved",
													"data" 		  => json_encode($data),
													"create_date" => $now->getTimestamp()
													);

				$approval_result			= $this->db->insert(Workflow::$TB_NAME_APPROVAL,$insert_arr);
				$approval_data				= (object) $insert_arr;
				
			} 

			

			
			if($approval_result){

				$insert_json				= $approval_data->data;
				$insert_arr					= json_decode($insert_json, true);
				$insert_arr['update_date']	= $now->getTimestamp();

  			$this->db->where('uuid', $insert_arr["uuid"]);
  			$app_result				= $this->db->delete(Workflow::$TB_NAME);
  
  			$this->db->where('uuid', $insert_arr["uuid"]);
  			$app_result				= $this->db->delete(Workflow::$TB_NAME_APP);


			}else{
				echo 'The record cannot be updated to '.Workflow::$TB_NAME_APPROVAL;
			}

			if(($approval_result && $app_result) == true) {
				redirect(''.$approval_redirect.'/?parent_uuid='.$insert_arr["parent_uuid"], 'refresh');
			}elseif($approval_result){
				echo "error in update approval<br>";
			}elseif($app_result){
				echo "error in insert table<br>";
			}

			*/

			if($app_result == true) {
				redirect(''.$approval_redirect.'/?parent_uuid='.$insert_arr["parent_uuid"], 'refresh');
			}

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
    }

    function discard(){
    	try{
			$now							= new DateTime();
			$uuid							= $this->uri->segment(3);
			$approval_redirect				= $this->session->userdata('approval_redirect');
			$this->db						= $this->load->database('lynx2_solutions',true);
			$this->getCurrentTable();


  			$this->db->where('parent_uuid', $uuid);
  			$this->db->where('action', 'Draft');
  			$this->db->where('author', $this->dx_auth->get_user_id());
  			$app_result				= $this->db->delete(Workflow::$TB_NAME_RESTORE);

  			$sql = 'SELECT * FROM '.Workflow::$TB_NAME.' WHERE uuid = "'.$uuid.'"';
			$query = $this->db->query($sql);
			$checkData = $query->result();
			foreach ($checkData as $rowData){
				$returnParentUUID = $rowData->parent_uuid;
				break;
			}

  			$sql = 'SELECT * FROM '.Workflow::$TB_NAME_APPROVAL.' WHERE parent_uuid = "'.$uuid.'"';
			$query = $this->db->query($sql);
			$checkData = $query->result();
			$checkRows = $query->num_rows();

			if($checkRows == 0){
				$this->db->where('parent_uuid', $uuid);
				$this->db->delete(Workflow::$TB_NAME_RESTORE);

				$this->db->where('uuid', $uuid);
				$this->db->delete(Workflow::$TB_NAME);
			}else{
				foreach ($checkData as $rowData){
					$returnRow = json_decode($rowData->data,true);
					$returnRow['create_date'] = $rowData->create_date;
					break;
				}

				$returnRowObject = (object)$returnRow;
				$update_arr					= array("name" 	 	  	=> $returnRowObject->name,
													"ext" 	  	  	=> $returnRowObject->ext,
													"title" 	  	=> $returnRowObject->title,
													"content" 	  	=> $returnRowObject->content,
													"keyword" 	  	=> $returnRowObject->keyword,
													"description" 	=> $returnRowObject->description,
													"type" 	  	  	=> $returnRowObject->type,
													"site" 		  	=> $returnRowObject->site,
													"template_id" 	=> $returnRowObject->template_id,
													"version" 	  	=> $returnRowObject->version,
													"status" 	  	=> $returnRowObject->status,
													"sequence"    	=> $returnRowObject->sequence,
													"menu" 		  	=> $returnRowObject->menu,
													"popup" 	  	=> $returnRowObject->popup,
													"alias" 	  	=> $returnRowObject->alias,
													"image" 	  	=> $returnRowObject->image,
													"effective_date"=> $returnRowObject->effective_date,
													"expire_date" 	=> $returnRowObject->expire_date,
													"author_id"  	=> $returnRowObject->author_id,
													"create_date" 	=> $returnRowObject->create_date,
													"update_date" 	=> $returnRowObject->update_date,
													);
				$this->db->where('uuid', $uuid);
				$this->db->update(Workflow::$TB_NAME,$update_arr);
			}
			redirect(''.$approval_redirect.'/?parent_uuid='.$returnParentUUID, 'refresh');
		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
    }



	function approvalList(){
		if ($this->dx_auth->is_logged_in())
		{
			if ($this->dx_auth->get_permission_value('approve') != NULL AND $this->dx_auth->get_permission_value('approve'))
			{
				try{
					$parent_uuid = $this->session->userdata('parent_uuid');
					// <----- basic
					$crud = new grocery_CRUD();
					$crud->set_theme('twitter-bootstrap');

					$this->db						= $this->load->database('lynx2_solutions',true);
					$this->getCurrentTable();

					$cur_state=$crud->getState();     

					/* Use the mySQL view to display the data with related tables */
					

					if($cur_state=="edit" || $cur_state=="update_validation" || $cur_state=="update")
					{
				        $crud->set_table(Workflow::$TB_NAME);
					}


					else     
					{
					   	$crud->set_table(Workflow::$TB_NAME_APPROVAL);
						$crud->set_primary_key('parent_uuid',Workflow::$TB_NAME_APPROVAL);
						$crud->where('action', 'waiting');	
						$crud->order_by('create_date','desc');
						$crud->display_as('template_id', 'Template');
						$crud->columns('title','tb_name', 'create_date', 'approval');                 
					}

					
					$crud->callback_column('create_date',array($this,'dateFormat'));
					$crud->callback_column('approval',array($this,'_get_workflow_action_button'));
					$crud->unset_delete();

					$crud->edit_fields('uuid','parent_uuid','type','name','alias','title','content','keyword','description','template_id','menu','sequence','image','effective_date','update_date');
					$crud->callback_edit_field('content', array($this, '_callback_content'));
					$crud->callback_edit_field('effective_date', array($this, '_callback_datepicker'));
					$crud->callback_before_update(array($this,'_callback_before_update'));
					$crud->callback_after_update(array($this,'_callback_after_update'));

					$crud->required_fields('type', 'title', 'name');
					$crud->field_type('uuid', 'hidden');
					$crud->field_type('parent_uuid', 'hidden');
					$crud->field_type('type', 'dropdown', $this->dropdown_type);
					$crud->field_type('update_date', 'hidden');
					$crud->field_type('template_id', 'dropdown', $this->template_id);
					$crud->set_field_upload('image', $this->image_url);

					if($parent_uuid){
						$row = $this->db->where('uuid', $parent_uuid)->get(Workflow::$TB_NAME)->row();
						$output->title = $this->_genPath($parent_uuid);
					}
					
					// <----- render
					$output = $crud->render();

					// <----- parent
					
					// <----- render
					$output->title = '<div>Approval List</div>';
					$this->load->view('approvalListView', $output);
					
				}catch(Exception $e){
					show_error($e->getMessage().' --- '.$e->getTraceAsString());
				}
			}
			else
			{
				$data['auth_message'] = 'Access denied for user.';	
				$this->load->view($this->dx_auth->logout_view, $data);
			}
		}
		
	}

	public function getCurrentTable(){
		Workflow::$TB_NAME				= $this->session->userdata('TB_NAME');
		Workflow::$TB_NAME_APP			= $this->session->userdata('TB_NAME_APP');
		Workflow::$TB_NAME_APPROVAL		= $this->session->userdata('TB_NAME_APPROVAL');
		Workflow::$TB_NAME_RESTORE		= $this->session->userdata('TB_NAME_RESTORE');
	}

	public function _callback_before_update($post_array, $primary_key){
		$purifier						= new HTMLPurifier();
		$now							= new DateTime();
		$date_1							= new DateTime($post_array['effective_date'], new DateTimeZone('UTC'));
		$user_id						= $this->dx_auth->get_user_id();

		
		$post_array['effective_date']	= $date_1->format('U');
		$post_array['title']			= $purifier->purify($post_array['title']);
		$post_array['name']				= $purifier->purify($post_array['name']);
		$post_array['alias']			= $purifier->purify($post_array['alias']);
		$post_array['update_date']		= $now->getTimestamp();
		$post_array['author_id']		= $user_id;

		
		
		unset($post_array['s78805a22']);
		return $post_array;
	}

	public function _callback_after_update($post_array, $primary_key){
		$this->mWorkflow->_callback_workflow_update($post_array, $primary_key);
		return $post_array;
	}

	public function _get_workflow_action_button($primary_key, $row){
		$action = $row->action;
		$result = null;
		if(strcasecmp($action, 'Approved') == 0){
			$result = null;
		}else{
			// $result = "<a title='Waiting for approval' class='glyphicon icon-font crud-action' href=".$this->config->item('base_url')."index.php/workflow/approve/".$row->uuid."></a>";
			$result = "<a title='Waiting for approval' style='color: #E84423;' href=".$this->config->item('base_url')."index.php/workflow/approve/approvalList/".$row->parent_uuid.">Approve</a>";
			$result .= str_repeat('&nbsp;', 1);
			$result .= "<a title='Waiting for reject' style='color: #0066cc;' href=".$this->config->item('base_url')."index.php/workflow/reject/approvalList/".$row->parent_uuid.">Reject</a>";
		}
		return $result;
	}

	

	public function _callback_content($value, $primary_key){

		//updated by Mark Torres for textarea bug in content #53577
		$value = stripslashes($value);
		$value = htmlspecialchars($value);

		return '<textarea style="height:200px;" type="text" maxlength="50" id="content" name="content">'.$value.'</textarea><script>
		window.UEDITOR_CONFIG.serverUrl += "?site='.$this->config->item('site_name').'&folder=PCCWS";
		var ue = UE.getEditor("content",  {
		toolbars: [["source", "undo", "redo", "bold", "insertimage", "link", "inserttable", "deletetable"]],
		autoHeightEnabled: true,
		autoFloatEnabled: true,
		  zIndex: 1,
		lang: "en"
		});</script>';
	}

	public function _callback_datepicker($value, $primary_key){

		//$this->print_arr($value);
		

		if($value == '' || $value == '0'){
			$now = new DateTime();
			$value	= $now->getTimestamp();
		}

		$date	= new DateTime('@'.$value, new DateTimeZone('UTC')); 
		//$this->print_arr($date->format('Y-m-d'));
		//exit();
		//$date	= new DateTime(); 
		//$date->setTimestamp($value);
		
		$str	= '<script>	
						var queryDate = "'.$date->format('Y-m-d').'",
						dateParts = queryDate.match(/(\d+)/g)
    					realDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
						
						$("#field-effective_date").datepicker("setDate", realDate);
					</script>';
		$str	= $str.'<input id="field-effective_date" name="effective_date" class="datepicker form-control" type="text" value="'.$date->format('Y-m-d').'">';
		
		//$str	= '<input id="field-effective_date" name="effective_date" class="form-control" type="text" value="'.$date->format('Y-m-d').'"><br>';
		//$str	+= '<input id="" name="effective_date" type="hidden" value="'.$date->format('Y-m-d').'">';
		return $str;
	}


	protected function _genPath($parent_uuid){
		$str = '';
		$query = $this->db->query($this->_genPathSql($parent_uuid));
		foreach ($query->result() as $row){
			if($row->parent_uuid)
				$str = '<a class="productPath" style="font-size:20px;" href="?parent_uuid='.$row->uuid.'">'.$row->title.'</a>&nbsp; '.($str?'\\':'').' &nbsp;'.$str;
			else
				$str = '<a class="productPath" style="font-size:36px;" href="?parent_uuid='.$row->uuid.'">'.$row->title.'</a>&nbsp; '.($str?'\\':'').' &nbsp;'.$str;
		}
		return $str;
	}

	protected function _genPathSql($parent_uuid, $level=5){
		$sql = '';
		$sql .= 'SELECT a . * , (';
		$sql .= '  SELECT @i := a.parent_uuid ';
		$sql .= '  FROM '.Workflow::$TB_NAME.' b ';
		$sql .= '  WHERE b.uuid = a.uuid ';
		$sql .= '  ) AS vars_i ';
		$sql .= 'FROM (';
		$sql .= '  SELECT @i :=  "'.$parent_uuid.'"';
		$sql .= ')vars, '.Workflow::$TB_NAME.' a ';
		$sql .= 'WHERE a.uuid = @i <>  "0"';

		for($i=1; $i<$level; $i++){
			$sql .= 'UNION ';
			$sql .= 'SELECT a . * , (';
			$sql .= '  SELECT @i := a.parent_uuid ';
			$sql .= '  FROM '.Workflow::$TB_NAME.' b ';
			$sql .= '  WHERE b.uuid = a.uuid ';
			$sql .= ') AS vars_i ';
			$sql .= 'FROM '.Workflow::$TB_NAME.' a ';
			$sql .= 'WHERE a.uuid = @i <>  "0"';
		}
		return $sql;
	}

	
	final function dateFormat($timestamp){
		return mdate($this->dateString, $timestamp);
	}
	
	//print array pe
	final static function print_arr($arr, $str=""){
		echo "-----------------------------";
		echo "<br/>";
		echo "<pre>";
		echo var_dump($arr);
		echo "</pre>";
		echo "<br/>";
		echo "-----------------------------";
		echo "<br/>";

	}
	
	final function testDate(){
	
		echo date_default_timezone_get() . "<br/>";
		$now = new DateTime();
		echo $timestamp = $now->getTimestamp();
		echo "<br/>";
		echo mdate($this->dateString, $timestamp);	
		
		date_default_timezone_set('Asia/Hong_Kong');
				
		echo "<br/><br/>";
		date_default_timezone_set('Asia/Hong_Kong');
		echo date_default_timezone_get() . "<br/>";	
		echo $timestamp = $now->getTimestamp();
		echo "<br/>";
		echo mdate($this->dateString, $timestamp);	
	}
	protected function restrictArray($tb_name, $arr){
		$sql = 'SHOW COLUMNS FROM '.$tb_name;
		$query = $this->db->query($sql);
		$data = $query->result_array();
		$new_arr = array();
		foreach($arr as $key => $value){
			foreach($data as $row){
				if($key==$row['Field']){
					$new_arr[$key] = $value;
					break;
				}
			}
		}
		return $new_arr;
	}

	protected function objectToArray($object)
	{
		$array	= array();

		foreach($object as $member=>$data)
		{
			$array[$member] = $data;
		}

		return $array;
	}
}

?>