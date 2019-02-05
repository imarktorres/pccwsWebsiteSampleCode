<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Administrator extends CI_Controller {

	private $dateString;
	private $imageUrl;

	public function __construct(){
		parent::__construct();
		$this->load->database();
		$this->load->helper('url');
		$this->load->helper('date');
		$this->load->library('grocery_CRUD');
		$this->load->library('DX_Auth');
		$this->load->library('Uuid');
		$this->dx_auth->check_uri_permissions(); 
		
		$this->dateString = '%Y-%m-%d %h:%i %a';
		$this->imageUrl = '../uploads/user';
		
		//var_dump($this->uri->segment_array());
		/*
		index
		  ajax_list
		  ajax_list_info
		  success
		  add
		  edit
		  upload_file
		  delete
		  delete_file
		*/
		}

		public function index(){
			try{
      // <----- basic
				$crud = new grocery_CRUD();
				$crud->set_theme('twitter-bootstrap');
				$crud->set_table('dx_users');
				$crud->order_by('username', 'asc');
				$crud->set_relation('role_id','dx_roles','{name}');
				$crud->display_as('role_id', 'Role');

			// <----- create
				$crud->add_fields('id','role_id', 'username', 'password', 'email','created');
				$crud->set_rules('username', 'Username','required|is_unique[dx_users.username]');
				$crud->set_rules('email', 'Email','required|is_unique[dx_users.email]');
				$crud->callback_before_insert(array($this,'_callback_before_insert'));
				// $crud->callback_add_field('email',array($this, '_callback_email'));
				$crud->callback_add_field('created',array($this, '_callback_created'));

			// <----- update
				$crud->edit_fields('role_id', 'username', 'email');
				$crud->callback_before_update(array($this,'_callback_before_update'));
				$crud->callback_edit_field('email',array($this, '_callback_email'));
//			$crud->callback_edit_field('password',array($this, '_callback_password'));

			// <----- read
				$crud->columns('role_id', 'username', 'email', 'last_ip', 'last_login', 'created', 'modified');
			//$crud->callback_column('create',array($this,'_callback_create'));
			//$crud->callback_column('modified',array($this,'_callback_modified'));

			// <----- delete
				$crud->callback_before_delete(array($this, '_callback_before_delete'));

      // <----- field setting
				$crud->required_fields('role_id', 'username', 'email', 'password');
				$crud->field_type('password', 'password');
				$crud->field_type('id', 'hidden');
				$crud->field_type('uuid', 'hidden');
				$crud->field_type('created', 'hidden');
			// <----- set permission
      //$crud->unset_add();
      //$crud->unset_edit();
      //$crud->unset_delete();
      //$crud->unset_export();
      //$crud->unset_list();

      // <----- render
				$output = $crud->render();
				$output->title = 'Administrator';
				$this->load->view('administratorView', $output);

			}catch(Exception $e){
				show_error($e->getMessage().' --- '.$e->getTraceAsString());
				return;
			}
		}
		public function _callback_before_insert($post_array){
			$post_array['id'] = $this->uuid->v5($post_array['username']);	
			$post_array['password'] = crypt($this->dx_auth->_encode($post_array['password']), 'rl');
			$post_array = $this->input->post('created');

			return $post_array;
		}
		public function _callback_before_update($post_array){
//    if($post_array['password'])
//      $post_array['password'] = crypt($this->dx_auth->_encode($post_array['password']), 'rl');

//	  $now = new DateTime();
//    $post_array['update_date'] = $now->getTimestamp();
			return $post_array;
		}
		public function _callback_password($value, $primary_key){
			return '<input name="password" type="password" value="" />';
//    return mdate($this->dateString, $value);
		}
// 		public function _callback_email($value, $primary_key){
// 			return '<input name="email" type="email" value="" />';
// //    return mdate($this->dateString, $value);
// 		}
		public function _callback_created($value, $primary_key){
			return '<input name="created" type="hidden" value="'.date("Y-m-d H:i:s").'" />';
//    return mdate($this->dateString, $value);
		}
		public function _callback_before_delete($primary_key){
//    $row = $this->db->where('uuid', $primary_key)->get('cms_user')->row();
//    @unlink($this->imageUrl.'/'.$row->image_url);
			return true;
		}
	}

	echo"<style>
		div#ajax-loading.hide.loading.show
		{
			display:none !important;
		}

	</style>";
