<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//ob_start();
require_once '../admin/assets/htmlpurifier/HTMLPurifier.auto.php';

class Item extends CI_Controller {
	private static $TB_NAME			= 'item';
	private static $TB_NAME_APP		= 'item_app';
	private static $TB_NAME_APPROVAL= 'item_approval';
	private static $TB_NAME_RESTORE	= 'item_versioning';  
	private $dateString;
	private $dropdown_type 			= array('4'=>'Other', '3'=>'Folder', '2'=>'Page', '1'=>'File', '0'=>'Misc');
	private $type 					= array('3'=>'3', '2'=>'2', '1'=>'1', '0'=>'0');
	private $template_id 			= array('blank'=>'blank', 'about_us'=>'about_us', 'blog'=>'blog', 'career'=>'career', 'career_bga'=>'career_bga', 'career_bgb'=>'career_bgb', 'career_bgc'=>'career_bgc', 'career_home'=>'career_home', 'cloud_computing'=>'cloud_computing', 'cloud_computing_baidu'=>'cloud_computing_baidu', 'contactus'=>'contactus', 'datacentre'=>'datacentre', 'D-Infinitum'=>'D-Infinitum', 'download'=>'download', 'event'=>'event', 'event_1_page'=>'event_1_page', 'event_home'=>'event_home', 'event_home_manual'=>'event_home_manual', 'event_single_page'=>'event_single_page','index'=>'index', 'industries'=>'industries', 'industries_Gov'=>'industries_Gov', 'industries_subFolder'=>'industries_subFolder', 'industries_blank'=>'industries_blank', 'news'=>'news', 'newsroom'=>'newsroom', 'newsroom_l'=>'newsroom_l', 'outsourcing'=>'outsourcing', 'pccw'=>'pccw', 'pccw_l'=>'pccw_l', 'pccw_l_ref'=>'pccw_l_ref', 'public'=>'public', 'software'=>'software', 'trainee_jacky'=>'trainee_jacky', 'sitemap'=>'sitemap', 'terms'=>'terms');
	private $db_pccws;
	private $db_default;  
	private $image_url 				= '../uploads/PCCWS/public';




	public function __construct(){
		parent::__construct();



		$this->load->database();
		$this->load->helper('url');
		$this->load->helper('date');
		
		$this->load->library('grocery_CRUD');
		$this->load->library('session');
		$this->load->library('DX_Auth');

		

		if ($this->dx_auth->is_logged_in()){
				
			$uriArray = $this->dx_auth->get_permission_value('uri');
	        $currentUri = explode("/",uri_string());
	        $validation = 'false';



	        if(!in_array("/", $uriArray)){
	            $counterForUriArray = 0;
	            while($counterForUriArray < count($uriArray) AND $validation == 'false'){
	                $testingUri = explode("/",$uriArray[$counterForUriArray]);

	                $counterForComparison =1;
	                while($counterForComparison<= $testingUri AND $validation == 'false'){
	                    if($testingUri[$counterForComparison] == $currentUri[$counterForComparison-1]){
	                        if(count($testingUri) - 2 == $counterForComparison){
	                            $validation = 'true';
	                        }
	                    } 
	                    else{
	                        break;
	                    }
	                    $counterForComparison++;
	                }
	                $counterForUriArray++;
	            }
	            if($validation == 'false'){
					$data['auth_message'] = 'Access denied for user.';	
					$this->load->view($this->dx_auth->logout_view, $data);	
				}
	        }
		} 
		else{
			$this->dx_auth->check_uri_permissions(); 
		}


		//$this->dx_auth->check_uri_permissions(); 

		$this->load->model('mVersioning');
		$this->mVersioning->setCurrentTable(Item::$TB_NAME, Item::$TB_NAME_RESTORE);
		
		$this->load->model('mWorkflow');
		$this->mWorkflow->setCurrentTable(Item::$TB_NAME, Item::$TB_NAME_APP, Item::$TB_NAME_APPROVAL, Item::$TB_NAME_RESTORE);
		
		$this->session->set_userdata('TB_NAME', Item::$TB_NAME);	
		$this->session->set_userdata('TB_NAME_APP', Item::$TB_NAME_APP);	
		$this->session->set_userdata('TB_NAME_APPROVAL', Item::$TB_NAME_APPROVAL);	
		$this->session->set_userdata('TB_NAME_RESTORE', Item::$TB_NAME_RESTORE);	

		$approval_redirect	= $this->uri->segment(3);
		$this->session->set_userdata('approval_redirect', $this->uri->segment(1).'/'.$this->uri->segment(2).'/'.$this->uri->segment(3));

		$this->dateString	= '%Y-%m-%d';

		$parent_uuid = @$_REQUEST['parent_uuid'];
		if($parent_uuid){
			$this->session->set_userdata('parent_uuid', $parent_uuid);			
		}
	}
	
	public function index(){
		try{

			$this->db		= $this->load->database('lynx2_solutions',true);
			$parent_uuid	= $this->session->userdata('parent_uuid');
			

			$crud			= new grocery_CRUD();
			$crud->set_theme('twitter-bootstrap');
			$crud->set_table(Item::$TB_NAME);
			$crud->set_primary_key('uuid','uuid');
			$crud->where('parent_uuid', $parent_uuid);
			$crud->where('type !=', $this->type['1']);
			$this->db->order_by('type desc, name asc');
			
			$crud->display_as('template_id', 'Template');




			if ($this->dx_auth->is_logged_in())
			{
				if ($this->dx_auth->get_permission_value('edit') != NULL AND $this->dx_auth->get_permission_value('edit'))
				{
					//$crud->set_edit();
				}
				else
				{
					$crud->unset_edit();
				}
				
				if ($this->dx_auth->get_permission_value('delete') != NULL AND $this->dx_auth->get_permission_value('delete'))
				{
					//$crud->set_delete();
				}
				else
				{
					$crud->unset_delete();
				}
			}



			$crud->add_fields('uuid','type','name','alias','title','content','keyword','description','site','parent_uuid','template_id','version','status','menu','sequence','image','effective_date','create_date','update_date');
			$crud->callback_add_field('parent_uuid',array($this, '_callback_parent_uuid'));
			$crud->callback_add_field('content',array($this, '_callback_content'));
			$crud->callback_add_field('effective_date',array($this, '_callback_datepicker'));
			$crud->callback_before_insert(array($this,'_callback_before_insert'));
			//$crud->callback_add_field('expire_date',array($this, '_callback_datepicker'));


			
			// <----- update
			$crud->edit_fields('uuid','parent_uuid','type','name','alias','title','content','keyword','description','template_id','menu','sequence','image','effective_date','update_date');
			$crud->callback_edit_field('content', array($this, '_callback_content'));
			$crud->callback_edit_field('effective_date', array($this, '_callback_datepicker'));
			//$crud->callback_edit_field('expire_date', array($this, '_callback_datepicker'));
			$crud->callback_before_update(array($this,'_callback_before_update'));
			$crud->callback_after_update(array($this,'_callback_after_update'));
			//$crud->callback_before_update(array($this->mVersioning, '_callback_backup_to_table'));
			//$crud->callback_edit_field('effective_date', array($this, '_callback_datepicker'));
			//$crud->callback_update(array($this,'_callback_update_test'));


			// <----- read
			$crud->columns('name','status','update_date','workflow');//shing edit
			$crud->add_action('Version Control', $this->config->item('base_url').'assets/images/Agreement-14.png', 'versioning/index/'.Item::$TB_NAME_APP, '');			
			$crud->add_action('Preview', $this->config->item('base_url').'assets/images/Agreement-15.png',  $this->config->item('base_url_public').'public/preview/', '');			
			$crud->callback_column('name', array($this,'_callback_name'));
			$crud->callback_column('update_date', array($this,'_callback_update_date'));
			$crud->callback_column('workflow',array($this,'_get_workflow_action_button'));
			$crud->callback_column('status',array($this,'_get_workflow_action'));
			

			// <----- delete
			$crud->callback_before_delete(array($this, '_callback_before_delete'));


			// <----- field setting
			$crud->required_fields('type', 'title', 'name');
			$crud->field_type('uuid', 'hidden');
			$crud->field_type('parent_uuid', 'hidden');
			$crud->field_type('type', 'dropdown', $this->dropdown_type);
			$crud->field_type('site', 'hidden');
			$crud->field_type('version', 'hidden');
			$crud->field_type('status', 'hidden');
			$crud->field_type('update_date', 'hidden');
			$crud->field_type('create_date', 'hidden');
			$crud->field_type('template_id', 'dropdown', $this->template_id);
			$crud->set_field_upload('image',$this->image_url);


			// <----- render
			$output					= $crud->render();
			$output->title			= 'Registration PCCW Solutions Day 2014';
			
			// <----- parent
			if($parent_uuid){
				$row				= $this->db->where('uuid', $parent_uuid)->get(Item::$TB_NAME)->row();				
				$output->title		= $this->_genPath($parent_uuid);
			}

			// <----- render
			$output->parent_uuid	 = $parent_uuid;


			$this->load->view('pccw_solutions/itemView', $output);
			
		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}
	}

	public function changeSequence(){

		try{

			$this->db				= $this->load->database('lynx2_solutions',true);
			$parent_uuid			= $this->session->userdata('parent_uuid');

			if(isset($_REQUEST['sequence'])){
				$sequence			= $_REQUEST['sequence'];
				$sequence_redirect	= $this->uri->segment(1).'/'.$this->uri->segment(2);
				//	echo '<br><br><br><br><br><br><br>';
				//var_dump($sequence);

				$update_arr			= array();
				$i = 0;
				foreach ($sequence as $key => $value){
					$i++;
					$update_arr[$i]['uuid']		= $key;
					$update_arr[$i]['sequence'] = $value;
				}
			
				$update1	= $this->db->update_batch(Item::$TB_NAME,$update_arr, 'uuid'); 
				$update2	= $this->db->update_batch(Item::$TB_NAME_APP,$update_arr, 'uuid'); 

				//var_dump($update1);
				//echo '<br>';
				//var_dump($sequence_redirect);
				//var_dump($this->uri->segment(3));

				if(($update1 && $update2) == true) {
					redirect('/'.$sequence_redirect.'/index'.'?parent_uuid='.$parent_uuid, 'refresh');
				}
			}


			//header("Content-Type: text/plain");

			
			

			
			$crud					= new grocery_CRUD();
			$crud->set_theme('twitter-bootstrap');			
			$crud->set_table(Item::$TB_NAME);
			$crud->set_primary_key('uuid');
			$output					= $crud->render();
			$output->parrent_uuid	= $this->uri->segment(4);
			$this->load->view('pccw_solutions/sequenceView', $output);

		}catch(Exception $e){
			show_error($e->getMessage().' --- '.$e->getTraceAsString());
		}

	}

	function getSequence() {
		$parent_uuid				= $this->uri->segment(4);
		$this->db					= $this->load->database('lynx2_solutions',true);
		$query						= $this->db->order_by('title', 'DESC')->get_where(Item::$TB_NAME, array('parent_uuid' => $parent_uuid),0,0);

		if( $query->num_rows() > 0 ) {
			$data					= $query->result();
        } else {
            $data					= array();
        }	
		
		echo json_encode($data);
	}


	function more_details($primary_key , $row)
	{
		//return site_url('demo/example/edit/'.$row->id.'?method=more_details');
	}
	
	
	public function _callback_before_update_test($post_array, $primary_key){
		$post_array['name'] = "vincent";
		$this->print_arr($post_array);

		return $post_array;
	}
	
	public function _callback_update_test($post_array, $primary_key){
		echo $primary_key;
		$this->print_arr($post_array);
		exit();
	}
	
	public function _callback_before_insert($post_array){
		$purifier						= new HTMLPurifier();
		$now							= new DateTime();
		$date_1							= new DateTime($post_array['effective_date'], new DateTimeZone('UTC'));
		$user_id						= $this->dx_auth->get_user_id();
		$post_array['uuid']				= $this->uuid->v5($post_array['name']);		
		$post_array['title']			= $purifier->purify($post_array['title']);
		$post_array['name']				= $purifier->purify($post_array['name']);
		$post_array['alias']			= $purifier->purify($post_array['alias']);
		$post_array['author_id']		= $user_id;
		$post_array['effective_date']	= $date_1->format('U');
		$post_array['update_date']		= $now->getTimestamp();
		$post_array['create_date']		= $now->getTimestamp();
		$this->mWorkflow->_callback_workflow_create($post_array);

		return $post_array;
	}

	public function _callback_before_update($post_array, $primary_key){

		//echo 'before<br>';
		//$this->print_arr($post_array);

		$purifier						= new HTMLPurifier();
		$now							= new DateTime();
		$date_1							= new DateTime($post_array['effective_date'], new DateTimeZone('UTC'));
		//$date_2							= new DateTime($post_array['expire_date'], new DateTimeZone('UTC'));
		$user_id						= $this->dx_auth->get_user_id();

		
		$post_array['effective_date']	= $date_1->format('U');
		//$post_array['expire_date']		= $date_2->format('U');
		$post_array['title']			= $purifier->purify($post_array['title']);
		$post_array['name']				= $purifier->purify($post_array['name']);
		$post_array['alias']			= $purifier->purify($post_array['alias']);
		$post_array['update_date']		= $now->getTimestamp();
		$post_array['author_id']		= $user_id;

		//echo '<br><br>after<br>';
		//$this->print_arr($post_array);
		//exit();
		
		unset($post_array['s78805a22']);
		return $post_array;
	}

	public function _callback_after_update($post_array, $primary_key){

		//echo '<script>console.log("1- '.$post_array['effective_date'].'")</script>';
		
		//$date 							= new DateTime($post_array['effective_date'], new DateTimeZone('UTC'));
		//$post_array['effective_date']	= (int)$date->format('U');
		/*
		$now							= new DateTime();
		$purifier						= new HTMLPurifier();
		$post_array['title']			= $purifier->purify($post_array['title']);
		$post_array['name']				= $purifier->purify($post_array['name']);
		$post_array['alias']			= $purifier->purify($post_array['alias']);	
		//$post_array['effective_date']	= '11111111111111111';	
		
		//$post_array['update_date']		= $now->getTimestamp();

		*/
		

		//echo '<script>console.log("2- '.$post_array['effective_date'].'")</script>';
		//var_dump($post_array);
		//exit();

		//unset($post_array['s78805a22']);

		//$this->print_arr($post_array);
		$this->mWorkflow->_callback_workflow_update($post_array, $primary_key);

		return $post_array;
	}
	

	public function _callback_name($value, $row){
		if($row->type==$this->type['3'])
			return '<a href="?parent_uuid='.$row->uuid.'" class="folder">'.$value.'</a>';
		return $value;
	}

	public function _callback_update_date($value, $row){
		//echo '<script>console.log('.$value.');</script>';
		
		if($value == ''){
			$value = '0';
		}
		
		$date = new DateTime('@'.$value, new DateTimeZone('UTC')); 
		if(isset($value) || $value == null || $value>= 0){
			//$date = new DateTime(''.$value, new DateTimeZone('UTC')); 
		}else{
			//$date = new DateTime('@'.$value, new DateTimeZone('UTC')); 
		}
		return $date->format('Y-m-d');
	}

	
	public function _callback_parent_uuid($value, $primary_key){
		return '<input type="hidden" name="parent_uuid" value="'.$this->session->userdata('parent_uuid').'" />';
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

	public function _callback_sequence($value, $row){
		if($row->sequence != null && isset($row->sequence))
			return '<input type="text" name="seq['.$row->uuid.']" value="'.$row->sequence.'">';
		return "--";
	}


	public function _callback_before_delete($primary_key){
		$this->mWorkflow->_callback_workflow_delete($primary_key);
		return false;
	}

	public function _get_workflow_action($primary_key, $row){
	  $result = ucfirst($this->mWorkflow->_get_workflow_action_waiting($row->uuid));
		return $result;
	}

	public function _get_workflow_action_button($primary_key, $row){
		$action = $row->status;
		$result = null;
		if($action == 'Approved'){
			$result = null;
		}else if($action == 'Waiting'){
			$result = "<a title='Waiting for approval' class='glyphicon icon-font crud-action' href=".$this->config->item('base_url')."index.php/workflow/approve/".$row->uuid."></a>";
		}else if($action == 'Deleted'){
			$result = "<a id='Waiting_for_deleted' title='Waiting for deleted' class='glyphicon icon-remove crud-action' href=".$this->config->item('base_url')."index.php/workflow/remove/".$row->uuid."></a>";
		}
		return $result;
	}
	

	protected function _genPath($parent_uuid){
		$str = '';
		$query = $this->db->query($this->_genPathSql($parent_uuid));
		foreach ($query->result() as $row){
			if($row->parent_uuid)
				$str = '<a class="productPath" style="font-size:20px;" href="'.$this->config->item('base_url').'index.php/pccw_solutions/item/index?parent_uuid='.$row->uuid.'">'.$row->name.'</a>&nbsp; '.($str?'\\':'').' &nbsp;'.$str;
			else
				$str = '<a class="productPath" style="font-size:36px;" href="'.$this->config->item('base_url').'index.php/pccw_solutions/item/index?parent_uuid='.$row->uuid.'">'.$row->name.'</a>&nbsp; '.($str?'\\':'').' &nbsp;'.$str;
		}
		return $str;
	}

	protected function _genPathSql($parent_uuid, $level=5){
		$sql = '';
		$sql .= 'SELECT a . * , (';
		$sql .= '  SELECT @i := a.parent_uuid ';
		$sql .= '  FROM '.Item::$TB_NAME.' b ';
		$sql .= '  WHERE b.uuid = a.uuid ';
		$sql .= '  ) AS vars_i ';
		$sql .= 'FROM (';
		$sql .= '  SELECT @i :=  "'.$parent_uuid.'"';
		$sql .= ')vars, '.Item::$TB_NAME.' a ';
		$sql .= 'WHERE a.uuid = @i <>  "0"';

		for($i=1; $i<$level; $i++){
			$sql .= 'UNION ';
			$sql .= 'SELECT a . * , (';
			$sql .= '  SELECT @i := a.parent_uuid ';
			$sql .= '  FROM '.Item::$TB_NAME.' b ';
			$sql .= '  WHERE b.uuid = a.uuid ';
			$sql .= ') AS vars_i ';
			$sql .= 'FROM '.Item::$TB_NAME.' a ';
			$sql .= 'WHERE a.uuid = @i <>  "0"';
		}
		return $sql;
	}


	final static function print_arr($arr, $str=""){
		echo "---------item page--------------------";
		echo "<br/>";
		echo "<pre>";
		echo var_dump($arr);
		echo "</pre>";
		echo "<br/>";
		echo "-----------------------------";
		echo "<br/>";

	}

}