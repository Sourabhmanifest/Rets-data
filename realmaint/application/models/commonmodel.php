<?php 
/*
this model is commonly used for all pages..
like index page, sign in etc.
*/

class Commonmodel extends CI_Model {

    function __construct()
    {
        // Call the Model constructor
        parent::__construct();
    }
	
	// this function returns table data.
	function getRecords($table, $fields="", $condition="", $orderby="", $single_row=false) //$condition is array 
	{
		if($fields != "")
		{
			$this->db->select($fields);
		}
		 
		if($orderby != "")
		{
			$this->db->order_by($orderby); 
		}

		if($condition != "")
		{
			$rs = $this->db->get_where($table,$condition);
		}
		else
		{
			$rs = $this->db->get($table);
		}
		
		if($single_row)
		{  
			return $rs->row_array();
		}
		return $rs->result_array();

	}

	function commonAddEdit($table_name, $data_array, $id = "")
	{
	
		if($table_name && is_array($data_array))
		{
			if($id == "")
			{	
				$query = $this->db->insert_string($table_name, $data_array);
			}
			else
			{
				$where = $table_name."_id = '$id'";
				$query = $this->db->update_string($table_name, $data_array, $where);
			}
			//echo $this->db->last_query();
			$this->db->query($query);
		}			
	}
	
	// function for deleting records by condition.
	function deleteRecords($table, $where)
	{ 
	
		$this->db->delete($table, $where); 
		//echo $a=$this->db->last_query();
	}
		
	// This function is used to set up mail configuration..
	function setMailConfig()
	{
		$this->load->library('email');
		$config['smtp_host'] = SMTP_HOST;
		$config['smtp_user'] = SMTP_USER;
		$config['smtp_pass'] = SMTP_PASS;
		$config['smtp_port'] = SMTP_PORT;
		$config['protocol'] = PROTOCOL;
		$config['mailpath'] = MAILPATH;
		$config['mailtype'] = MAILTYPE;
		$config['charset'] = CHARSET;
		$config['wordwrap'] = WORD_WRAP;

		$this->email->initialize($config);
	}

	function sendEmail()
	{
		$this->email->send();
	}
	
	function is_login(){
		$cur_user = $this->session->userdata('user');			
		if (empty($cur_user)) {
			$cookie_hash = $this->input->cookie('user');
			if (!empty($cookie_hash)){
				$cur_user = $this->getRecords('user','user_id, is_active, is_block', array("cookie_hash"=>$cookie_hash),'',true);
				if(!empty($cur_user)){
					 $this->set_login($cur_user,true);
				}
			}
		}
		
		return empty($cur_user)?false:true;
	}
	
	function cur_user_id(){
		$cur_user = $this->session->userdata('user');			
		return empty($cur_user)?0:$cur_user['user_id'];
	}
	
	function cur_user_info(){
		$cur_user = $this->session->userdata('user');	
		if(!empty($cur_user)){
			return $this->getRecords("user", "*", array('user_id'=>$cur_user['user_id']),'',true);
		}
	}
	
	function set_login($user_data, $keep=false){
		$this->session->set_userdata(array('user'=>$user_data));		
		if($keep){
			$cookie_hash = uniqid('ufree');
			$this->commonAddEdit('user', array('cookie_hash'=> md5($cookie_hash)), $user_data['user_id']);
			$cookie = array(
				'name'   => 'user',
				'value'  => md5($cookie_hash),
				'expire' => '86500',
				'domain' => '',
				'path'   => '/',
				'prefix' => '',
				'secure' => false
			);
			$this->input->set_cookie($cookie);
		}
	}
	
	function set_logout(){		
		$this->session->unset_userdata('user');
		$cookie = array(
			'name'   => 'user',
			'value'  => false,
			'expire' => '',
			'domain' => '',
			'path'   => '/',
			'prefix' => '',
			'secure' => false
		);
		$this->input->set_cookie($cookie);
	}
}

	