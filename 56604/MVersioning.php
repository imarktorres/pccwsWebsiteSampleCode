<?php 
class mVersioning extends CI_Model {
	
	private static $TB_NAME = 'item';
	private static $BK_TB_NAME = 'item_versioning';
	private static $TB_NAME_APPROVAL= 'item_approval';
	private static $action = '';




	public function setCurrentTable($c_table, $c_table_versioning = NULL){
		mVersioning::$TB_NAME		= $c_table;
		
		if($c_table_versioning != NULL && isset($c_table_versioning)){
			mVersioning::$BK_TB_NAME	= $c_table_versioning;
		}
		
	}
	
    public function getAll() {
        $query = $this->db->get( mVersioning::$BK_TB_NAME );
        if( $query->num_rows() > 0 ) {
            return $query->result();
        } else {
            return array();
        }
    }
	
	public function getByParent_uuid($parent_uuid){
		$this->db					= $this->load->database('lynx2_solutions',true);
		$query 						= $this->db->order_by('create_date', 'DESC')->get_where(mVersioning::$BK_TB_NAME, array('parent_uuid' => $parent_uuid, 'action !=' => 'Draft'), 0, 0);

		$removalFirstElement = $query->result();
		array_shift($removalFirstElement);

		if( count($removalFirstElement) > 0 ) {
			return $removalFirstElement;
        } else {
            return array();
        }
	}

	public function restoreVersion($uuid){
			$this->db					= $this->load->database('lynx2_solutions',true);
			
			$now = new DateTime();
		
			//get newly backup data to array.
			$sql = 'SELECT * FROM '.MVersioning::$BK_TB_NAME.' WHERE uuid = "'.$uuid.'" order by create_date asc;';
			$query = $this->db->query($sql);
			$data = $query->result();
			$data = $data[0];

			


			//set the Resotre table
			//Versioning::$ResotreTo_NAME = $data->tb_name;

			//get current data, ready to backup.
			$c_sql = 'SELECT * FROM '.MVersioning::$TB_NAME.' WHERE uuid = "'.$data->parent_uuid.'";';
			$c_query = $this->db->query($c_sql);
			$c_data = $c_query->result();
			$c_data = $c_data[0];



			//copy the current data and update to newly backup data.	
			$backup_arr = array("uuid" 		  => $this->uuid->v5($c_data->title),
								"parent_uuid" => $c_data->uuid,
								"tb_name" 	  => MVersioning::$TB_NAME,
								"title" 	  => $data->title,
								"data" 		  => $data->data,
								"create_date" => $now->getTimestamp(),
			);


			
			$this->db->where('uuid', $data->uuid);
			$backup = $this->db->update(MVersioning::$BK_TB_NAME, $backup_arr);



			$backup_arr['action'] = "Waiting";
			$this->db->where('parent_uuid', $data->parent_uuid);
			$backup = $this->db->update(MVersioning::$TB_NAME_APPROVAL, $backup_arr);


			//copy the current data and update to newly backup data.
			$restore_json = $data->data;
			$restore_arr = json_decode($restore_json, true);
			$restore_arr['update_date'] = $now->getTimestamp();
			$restore_arr['create_date'] = $now->getTimestamp();
			$this->db->where('uuid', $restore_arr['uuid']);
			$restore = $this->db->update(MVersioning::$TB_NAME, $restore_arr);

			return $restore_arr['parent_uuid'];
	}
	

	
	public function _current_action($action){
		mVersioning::$action = $action;
	}
	
	public function _callback_backup_to_table($post_array,$primary_key){ 
		//$this->print_arr($post_array);
		/*//get current data.
		$sql = 'SELECT * FROM '.mVersioning::$TB_NAME.' WHERE uuid = "'.$primary_key.'"';
		$query = $this->db->query($sql);
		$data = $query->result();
		$data = $data[0];

		//$this->print_arr("------_callback_versioning-----");
		//$this->print_arr($sql);

		//insert backup data.
		$now = new DateTime();
		$backup_insert = array(
			"uuid" 		  => $this->uuid->v5($data->title),
			"parent_uuid" => $data->uuid,
			"tb_name" 	  => mVersioning::$TB_NAME,
			"title" 	  => $data->title,
			"action" 	  => mVersioning::$action,
			"data" 		  => json_encode($data),
			"create_date" => $now->getTimestamp(),
		);
		$this->db->insert(mVersioning::$BK_TB_NAME,$backup_insert);
		*/
		//return true;
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