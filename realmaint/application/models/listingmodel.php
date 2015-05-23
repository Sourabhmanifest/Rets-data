<?php 
/*
this model is commonly used for all pages..
like index page, sign in etc.
*/

class Listingmodel extends CI_Model {

		function __construct()
		{
			// Call the Model constructor
			parent::__construct();
		}

		function getListStreet($keyword, $search_by="StreetName", $ft=0)
		{ 
			if($search_by=="Latitude")
			{ 
				$latlong = explode('~~',$keyword);
				$latitude = $latlong[0];
				$longitude = $latlong[1];
				switch($ft)
				{
					case 9:
						$diff = 0.000015;
						break;
					case 19:
						$diff = 0.000030;
						break;
					case 28:
						$diff = 0.000045;
				}

				echo $condition = "(Real_Location.Latitude BETWEEN ".($latitude - $diff)." AND ".($latitude +$diff).") AND (Real_Location.Longitude BETWEEN ".($longitude - $diff)." AND ".($longitude + $diff).") ";

				$streets = $this->db->select('*')->from('Real_Location')->join('Location_Listing', 'Location_Listing.Location_id = Real_Location.Location_id')->where($condition)->order_by("StreetName", "StreetNumber")->get()->result_array();
			}
			else if($search_by=="Polygon_id")
			{
				$streets = $this->db->select('*')->from('Real_Location')->join('Real_Polygon', 'Real_Polygon.Location_id = Real_Location.Location_id')->join('Location_Listing', 'Location_Listing.Location_id = Real_Location.Location_id')->where('Real_Polygon.'.$search_by, $keyword)->order_by("StreetName", "StreetNumber")->get()->result_array(); 
			}
			else
			{
				$streets = $this->db->select('*')->from('Real_Location')->join('Location_Listing', 'Location_Listing.Location_id = Real_Location.Location_id')->where('Real_Location.'.$search_by, $keyword)->order_by("StreetName", "StreetNumber")->get()->result_array();
			}
			
			return $streets;
		}
			
		function getStatusProcess($process)
		{ 
			$this->db->select('*');
			$this->db->order_by('DateTime', 'DESC');
			$this->db->limit('14');
			$rs = $this->db->get_where('Process_Status_Log',array('Process'=>$process));

			return $rs->result_array();
		}

		function saveKeyword($post_array, $KeyWord_id)
		{
			$data_array['QDNA']		= $post_array['qdna'];
			$data_array['KeyWord']	= strtolower($post_array['keyword']);
			$data_array['Adjective']	= strtolower($post_array['adjective']);
			$data_array['Token']		= $post_array['token'];
			$data_array['JumpKey']	= $post_array['jumpkey'];
			$data_array['Seq']		= $post_array['seq'];
			$data_array['Relative']		= $post_array['relative'];
			$data_array['Weight']		= $post_array['wt'];

			
			if($KeyWord_id == "0")
			{	
				$query = $this->db->insert_string('Keyword_Phrase', $data_array);
			}
			else
			{
				$query = $this->db->update_string('Keyword_Phrase', $data_array, array('KeyWord_id'=>$KeyWord_id));
			}
			$this->db->query($query);			
		}

		function saveUser($post_array, $User_id = "")
		{
			$data_array['Username']	= strtolower($post_array['uname']);
			$data_array['Fullname']	=($post_array['fullname']);
			$data_array['Email']		= strtolower($post_array['email']);
			$data_array['Role']		= $post_array['role'];	
			
			if($post_array['newpwd'] != '')
			{
				$data_array['Password']	= CRC32($data_array['Email'].$post_array['newpwd']);
			}
			
					
			if($User_id == "")
			{	
				$query = $this->db->insert_string('Real_Users', $data_array);
			}
			else
			{
				$query = $this->db->update_string('Real_Users', $data_array, array('User_id'=>$User_id));
			}
			$this->db->query($query);			
		}



		function insertId($table_name, $data_array)
		{
			foreach($data_array as $row){
				$this->db->insert($table_name, $row);
			}
		}

		function getPropertyId($token)
		{
			//echo'<pre>';print_r($token);exit;
			$data = $this->db->query('SELECT Property_id FROM `Real_QWords` where Token LIKE "'.$token.'" and Property_id NOT IN								(SELECT Property_id FROM Keyword_Rerun) order by Property_id');	
			echo'<pre>';print_r($data->result());exit;
			foreach($data->result() as $row)
			{
				for($i=0;$i<sizeof($row);$i++){
					echo $row['Property_id'];			
				}
				//echo '<pre>'; $row['Property_id'];exit;
			}
			return $data->result_array();
		}

		/*    Insert Photo Comment   */

		function saveTag($post_array)
		{
			
			if($post_array['find']==0)
			{	
				$data_array['Photo_id']= $post_array['Photo_id'];
				$data_array['Comment']= $post_array['tag'];
				$data_array['User_id']= $post_array['User_id'];
				$data_array['Property_id']= $post_array['Property_id'];
				$data_array['Entered']=date('Y-m-d h:i:s');
				
				$query = $this->db->insert_string('Public_Text', $data_array);
			}
			if($post_array['find']==1)
			{	
				$data_array['Photo_id']= $post_array['Photo_id'];
				$data_array['Comment']= $post_array['tag'];
				$data_array['Modified']=date('Y-m-d h:i:s');
				$query = $this->db->update_string('Public_Text', $data_array, array('Photo_id'=>$data_array['Photo_id']));
			}
			$this->db->query($query);			
		}

		function getRecords ($Process_Schedule,$Process_Execution)
		{
			$data = $this->db->query('SELECT * FROM `Process_Execution` JOIN `Process_Schedule` ON `Process_Execution`.`Process_id`= `Process_Schedule`.`Process_id` ORDER BY  `Process_Schedule`.`Process_id`');	
			//echo'<pre>';print_r($data->result());exit;
			return $data->result_array();
		}

		function saveAreaNames($post_array, $User_id = "")
		{
			$data_array['Username']	= strtolower($post_array['uname']);
			$data_array['Fullname']	=($post_array['fullname']);
			$data_array['Email']		= strtolower($post_array['email']);
			$data_array['Role']		= $post_array['role'];	
		
					
			if($User_id == "")
			{	
				$query = $this->db->insert_string('Real_Users', $data_array);
			}
			else
			{
				$query = $this->db->update_string('Real_Users', $data_array, array('User_id'=>$User_id));
			}
			//$this->db->query($query);			
		}

		function updateAreaNames($table_name, $data_array, $where)
		{
			$query = $this->db->update_string($table_name, $data_array, $where);
			$this->db->query($query);
		}

		function commonInsertUpdate($table_name,$data_array,$where)
		{
			if($table_name && is_array($data_array))
			{
				if($where=="")
				{
					$query = $this->db->insert_string($table_name,$data_array);
				}
				else
				{
					$query = $this->db->update_string($table_name, $data_array, $where);
				}
				//echo $this->db->last_query();exit;
				$this->db->query($query);
				//echo $this->db->last_query();exit;
			}			
		}
	}

	