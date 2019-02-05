<?php 
class mWorkflow extends CI_Model {
	
	private static $TB_NAME				= 'lynx2_product';
	private static $TB_NAME_APP			= 'lynx2_product_app';
	private static $TB_NAME_APPROVAL	= 'lynx2_approval';

	private static $action_waiting		= 'waiting';
	private static $action_create		= 'create';
	private static $action_update		= 'update';
	private static $status				= 'waiting';
	private static $status_del			= 'deleted';
	private static $status_approved		= 'approved';
	private static $TB_NAME_RESTORE		= 'item_versioning'; 



	public function setCurrentTable($c_table, $c_table_app = NULL, $c_table_approval = NULL, $c_table_restore){
		mWorkflow::$TB_NAME				= $c_table;
		if($c_table_app != NULL && isset($c_table_app))
			mWorkflow::$TB_NAME_APP		= $c_table_app;
		if($c_table_approval != NULL && isset($c_table_approval))
			mWorkflow::$TB_NAME_APPROVAL= $c_table_approval;
		if($c_table_restore != NULL && isset($c_table_restore))
			mWorkflow::$TB_NAME_RESTORE= $c_table_restore;
	}
	
    public function getAll() {
        $query = $this->db->get( mWorkflow::$TB_NAME_APP );
        if( $query->num_rows() > 0 ) {
            return $query->result();
        } else {
            return array();
        }
    }


	
	public function getByParent_uuid($parent_uuid){
		$query = $this->db->order_by('create_date', 'DESC')->get_where(mWorkflow::$BK_TB_NAME, array('parent_uuid' => $parent_uuid), 0, 0);
		if( $query->num_rows() > 0 ) {
			return $query->result();
        } else {
            return array();
        }
	}
	

	/*
	public function getCurrentTable(){
		$TB_arr['TB_NAME']				= mWorkflow::$TB_NAME;
		$TB_arr['TB_NAME_APP']			= mWorkflow::$TB_NAME_APP;
		$TB_arr['TB_NAME_APPROVAL']		= mWorkflow::$TB_NAME_APPROVAL;

		return $TB_arr;
	}
	*/
	
	public function _current_action($action){
		mWorkflow::$action 				= $action;
	}
	
	public function _callback_backup_to_table($post_array,$primary_key){ 
		//get current data.
		$sql							= 'SELECT * FROM '.mWorkflow::$TB_NAME_APPROVAL.' WHERE uuid = "'.$primary_key.'"';
		$query							= $this->db->query($sql);
		$data							= $query->result();
		$data							= $data[0];

		//insert backup data.
		$now = new DateTime();
		$backup_insert 					= array("uuid" 		  => $this->uuid->v5($data->title),
												"parent_uuid" => $data->uuid,
												"tb_name" 	  => mWorkflow::$TB_NAME_APPROVAL,
												"title" 	  => $data->title,
												"action" 	  => mWorkflow::$action,
												"data" 		  => json_encode($data),
												"create_date" => $now->getTimestamp(),
											);

		$this->db->insert(mWorkflow::$BK_TB_NAME,$backup_insert);
		return true;
	  }

	public function _callback_workflow_create($post_array){ 

		//$this->print_arr($post_array);
		//exit();

		if (array_key_exists("s78805a22",$post_array)){
			unset($post_array["s78805a22"]);
		}

		//$this->print_arr($post_array);
			//exit();
		
		$now							= new DateTime();
		$insert_arr 					= array("uuid" 		  => $this->uuid->v5($post_array["title"]),
												"parent_uuid" => $post_array["uuid"],
												"tb_name" 	  => mWorkflow::$TB_NAME,
												"title" 	  => $post_array["title"],
												"action" 	  => mWorkflow::$status,
												"data" 		  => json_encode($post_array),
												"create_date" => $now->getTimestamp(),
												);

		$this->db->insert(mWorkflow::$TB_NAME_APPROVAL,$insert_arr);
		//$insert_arr['action'] = "";
		//updated by Evan to insert action and user
		$insert_arr['action'] = mWorkflow::$action_create;
		$insert_arr['author'] = $post_array["author_id"];

		$this->db->insert(mWorkflow::$TB_NAME_RESTORE,$insert_arr);
		
		return true;
	}
	
	public function _callback_workflow_update($post_array, $primary_key){

		//$this->print_arr('post_array');
		//$this->print_arr($post_array);

		//exit();
		

		$now							= new DateTime();

		$this->db->where('parent_uuid', $primary_key);
		$this->db->where('action', 'Draft');
  		$this->db->delete(mWorkflow::$TB_NAME_RESTORE);

		$approval_sql 					= 'SELECT * FROM '.mWorkflow::$TB_NAME_APPROVAL.' WHERE parent_uuid = "'.$primary_key.'"';
		$approval_query					= $this->db->query($approval_sql);
		$approval_data 					= $approval_query->result();


		
		if(strcmp($post_array["status"], "Draft") != 0){
			$post_array["status"] = mWorkflow::$status;

			if($approval_data != null && isset($approval_data)){
				//$approval_data 				= $approval_data[0];
				//$approval_data_json			= $approval_data->data;
				//$approval_data_arr			= json_decode($approval_data_json, true);
				//$post_array["uuid"]			= $approval_data_arr["uuid"];
				//$post_array["parent_uuid"]	= $approval_data_arr["parent_uuid"];
				//$post_array['uuid']			= $primary_key;

				$update_arr					= array("tb_name" 	  => mWorkflow::$TB_NAME,
													"title" 	  => $post_array["title"],
													"action" 	  => mWorkflow::$status,
													"data" 		  => json_encode($post_array),
													"create_date" => $now->getTimestamp(),
													);
				$this->db->where('parent_uuid', $primary_key);
				$approval_result = $this->db->update(mWorkflow::$TB_NAME_APPROVAL,$update_arr);
			}else{

				$insert_arr 				= array("uuid" 		  => $this->uuid->v5($post_array["title"]),
													"parent_uuid" => $post_array["uuid"],
													"tb_name" 	  => mWorkflow::$TB_NAME,
													"title" 	  => $post_array["title"],
													"action" 	  => mWorkflow::$status,
													"data" 		  => json_encode($post_array),
													"create_date" => $now->getTimestamp(),
													);
				$approval_result = $this->db->insert(mWorkflow::$TB_NAME_APPROVAL,$insert_arr);
				//$this->print_arr($insert_arr);
			}
		}

		if($approval_result || strcmp($post_array["status"], "Draft") == 0){


			$now							= new DateTime();
			$insert_arr 					= array("uuid" 		  => $this->uuid->v5($post_array["title"]),
													"parent_uuid" => $post_array["uuid"],
													"tb_name" 	  => mWorkflow::$TB_NAME,
													"title" 	  => $post_array["title"],
													"data" 		  => json_encode($post_array),
													"create_date" => $now->getTimestamp(),
													//updated by Evan to insert action and user
													'action'	  => $post_array["status"],
													'author'	  => $post_array["author_id"],
													);
			$this->db->insert(mWorkflow::$TB_NAME_RESTORE,$insert_arr);

		}
		else{
			echo 'The record cannot be updated to '.Workflow::$TB_NAME_APPROVAL;
		}
		return true;
	}

	public function _callback_workflow_delete($primary_key){

		$update_arr 	= array("status" 	  => "");
		$this->db->where('uuid', $primary_key);
		$this->db->update(mWorkflow::$TB_NAME,$update_arr);
		$this->db->reset_query();

		$approval_sql					= 'SELECT * FROM '.mWorkflow::$TB_NAME_APPROVAL.' WHERE parent_uuid = "'.$primary_key.'"';
		$approval_query					= $this->db->query($approval_sql);
		$approval_query_data			= $approval_query->result();
		$approval_query_data			= $approval_query_data[0];

		$now = new DateTime();

		$approval_update_arr 			= array("action" 	  => "deleted",
											"create_date" => $now->getTimestamp(),
											);
		
		$this->db->reset_query();
		$this->db->where('uuid', $approval_query_data->uuid);
		$this->db->update(mWorkflow::$TB_NAME_APPROVAL,$approval_update_arr);

		
		$this->db->reset_query();
		$app_sql						= 'SELECT * FROM '.mWorkflow::$TB_NAME_APPROVAL.' WHERE parent_uuid = "'.$primary_key.'"';
		$app_query						= $this->db->query($app_sql);
		$app_query_data					= $app_query->result();
		$app_query_data					= $app_query_data[0];

		$app_update_arr 			= array("status" 	  => "deleted",
											"create_date" => $now->getTimestamp(),
											);

		$this->db->reset_query();
		$this->db->where('uuid', $app_query_data->uuid);
		$this->db->update(mWorkflow::$TB_NAME_APP,$app_update_arr);


		$app_update_arr 			= array("status" 	  => "deleted");
		$this->db->reset_query();
		$this->db->where('uuid', $primary_key);
		$this->db->update(mWorkflow::$TB_NAME,$app_update_arr);


//
//
//		$sql							= 'SELECT * FROM '.mWorkflow::$TB_NAME.' WHERE uuid = "'.$primary_key.'"';
//		$query							= $this->db->query($sql);
//		$query_data						= $query->result();
//		$query_data						= $query_data[0];
//
//		$app_sql						= 'SELECT * FROM '.mWorkflow::$TB_NAME_APP.' WHERE uuid = "'.$primary_key.'"';
//		$app_query						= $this->db->query($app_sql);
//		$app_query_data					= $app_query->result();
//		$app_query_data					= $app_query_data[0];
//
//		if(($query_data != null && isset($query_data)) && ($app_query_data != null && isset($app_query_data))){
//			
//			$this->db->where('uuid', $query_data->uuid);
//			$insert_result				= $this->db->delete(mWorkflow::$TB_NAME);
//
//			$this->db->where('uuid', $query_data->uuid);
//			$insert_result				= $this->db->delete(mWorkflow::$TB_NAME_APP);
//
//
//		}else{
//			//$insert_result = $this->db->insert(Workflow::$TB_NAME,$insert_arr);
//
//			echo "record can not be deleted";
//		}
//
//
//

		return true;
	}

	public function _get_workflow_action_waiting($primary_key){
		$sql							= 'SELECT * FROM '.mWorkflow::$TB_NAME_APPROVAL.' WHERE parent_uuid = "'.$primary_key.'"';//and action = "'.mWorkflow::$status.'"';
		$query							= $this->db->query($sql);
		$query_data						= $query->result();

		if($query_data != null && isset($query_data)){
			$query_data					= $query_data[0];
			$workflow_action			= $query_data->action;
		}else{
			//$workflow_action			= "waiting";
			$workflow_action			= mWorkflow::$status;
		}
		
		return $workflow_action;
	}

	public function _callback_check_name($check_array){
		if(empty($check_array['uuid'])){
			$sql = 'SELECT * FROM '.mWorkflow::$TB_NAME.' WHERE parent_uuid = "'.$check_array['parent_uuid'].'" and name = "'.$check_array['name'].'" and type = "'.$check_array['type'].'"';
		}else{
			$sql = 'SELECT * FROM '.mWorkflow::$TB_NAME.' WHERE parent_uuid = "'.$check_array['parent_uuid'].'" and name = "'.$check_array['name'].'" and uuid <> "'.$check_array['uuid'].'" and type = "'.$check_array['type'].'"';
		}
		$query							= $this->db->query($sql);
		$data 							= $query->result();

		if(!empty($data)){
			return FALSE;
		}else{
			return TRUE;
		}
	}

	public function _callback_check_draft($currentUser){
		$sql = 'SELECT * FROM '.mWorkflow::$TB_NAME_RESTORE.' WHERE action = "Draft" AND author = "'.$currentUser.'"';
		$query = $this->db->query($sql);
		$checkData 	= $query->result();

		foreach ($checkData as $rowData){
			$this->db->reset_query();
			$returnRow = json_decode($rowData->data,true);
			$returnRow['create_date'] = $rowData->create_date;
			$returnRow['status'] = $rowData->action;
			
			$sql = 'SELECT * FROM '.mWorkflow::$TB_NAME.' WHERE uuid = "'.$returnRow['uuid'].'"';
			$query = $this->db->query($sql);
			$numRows = $query->num_rows();
			$this->db->reset_query();

			$returnRowObject = (object)$returnRow;
			if($returnRowObject->expire_date == NULL){
				$returnRowObject->expire_date = 0;
			}

			if($returnRowObject->menu == NULL){
				$returnRowObject->menu = 0;
			}

			if($returnRowObject->popup == NULL){
				$returnRowObject->popup = 0;
			}

			$data_arr					= array("name" 	 	  	=> $returnRowObject->name,
												"ext" 	  	  	=> $returnRowObject->ext,
												"title" 	  	=> $returnRowObject->title,
												"content" 	  	=> $returnRowObject->content,
												"keyword" 	  	=> $returnRowObject->keyword,
												"description" 	=> $returnRowObject->description,
												"type" 	  	  	=> $returnRowObject->type,
												"site" 		  	=> $returnRowObject->site,
												"template_id" 	=> $returnRowObject->template_id,
												"sequence"    	=> $returnRowObject->sequence,
												"create_date" 	=> $returnRowObject->create_date,
												"update_date" 	=> $returnRowObject->update_date,
												"alias" 	  	=> $returnRowObject->alias,
												"image" 	  	=> $returnRowObject->image,
												"effective_date"=> $returnRowObject->effective_date,
												"author_id"  	=> $returnRowObject->author_id,
												"status" 	  	=> $returnRowObject->status,
												"menu" 		  	=> $returnRowObject->menu,
												"expire_date" 	=> $returnRowObject->expire_date,
												"popup" 	  	=> $returnRowObject->popup,
												);
			
			

			if($numRows == 0){
				$data_arr["uuid"] 			= $returnRowObject->uuid;
				$data_arr["parent_uuid"]	= $returnRowObject->parent_uuid;
				$this->db->insert(mWorkflow::$TB_NAME,$data_arr);
			}else{
				$this->db->where('uuid', $returnRowObject->uuid);
				$this->db->update(mWorkflow::$TB_NAME,$data_arr);
			}
			$this->db->reset_query();			
		}
		
		return NULL;
	}

	public function _callback_check_otherVersions($currentUser){
		$sql = 'SELECT * FROM '.mWorkflow::$TB_NAME.' WHERE status = "Draft" AND author_id <> "'.$currentUser.'"';
		$query = $this->db->query($sql);
		$checkData 	= $query->result();

		foreach ($checkData as $rowData){
			$latestUUID = $rowData->uuid;

			$this->db->reset_query();
			$sql = 'SELECT * FROM '.mWorkflow::$TB_NAME_RESTORE.' WHERE parent_uuid = "'.$latestUUID.'"';
			$query = $this->db->query($sql);
			$checkRows = $query->num_rows();

			if($checkRows <= 1){
				$this->db->reset_query();
				$this->db->where('uuid', $latestUUID);
		  		$this->db->delete(mWorkflow::$TB_NAME);
			}else{
				$this->db->reset_query();
				$sql = 'SELECT * FROM '.mWorkflow::$TB_NAME_RESTORE.' WHERE parent_uuid = "'.$latestUUID.'" and author = "'.$currentUser.'" and action = "Draft" ORDER BY create_date desc';
				$query = $this->db->query($sql);
				$checkRowData = $query->result();
				$checkNumberOfRows = $query->num_rows();

				if($checkNumberOfRows <=0){
					$this->db->reset_query();
					$sql = 'SELECT * FROM '.mWorkflow::$TB_NAME_APPROVAL.' WHERE parent_uuid = "'.$latestUUID.'"';
					$query = $this->db->query($sql);
					$checkRowData = $query->result();
				}
				foreach ($checkRowData as $checkDataRow){
			        $returnRow = json_decode($checkDataRow->data,true);
			        $returnRow['create_date'] = $checkDataRow->create_date;
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
				$this->db->reset_query();
				$this->db->where('uuid', $latestUUID);
				$this->db->update(mWorkflow::$TB_NAME,$update_arr);	
				$this->db->reset_query();
			}
		}
		return NULL;
	}

	final static function print_arr($arr, $str=""){
		echo "-------------mWorkflow----------------";
		echo "<br/>";
		echo "<pre>";
		echo var_dump($arr);
		echo "</pre>";
		echo "<br/>";
		echo "-----------------------------";
		echo "<br/>";

	}

}
?>