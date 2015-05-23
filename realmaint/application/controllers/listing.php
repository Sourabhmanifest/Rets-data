<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Listing extends CI_Controller {
	function __construct()
	{
		
		parent::__construct();
		
		$this->load->database();
		$this->load->library('session');
		$this->load->helper(array('form', 'url'));
		$this->load->model(array('listingmodel','commonmodel'));
		$this->load->library('form_validation');
	}

	/**
	 * Index fnction for this controller.
	 */
	public function index()
	{
		if($this->session->userdata('User_id')!='' && $this->session->userdata('email')!='')
		{
			$this->home();
		}
		else
		{
			$this->load->view('includes/header');
			$this->load->view('login');
			$this->load->view('includes/footer');
		}
	}

	/**
	 * checkAuth fnction for this controller.
	 */
	public function checkAuth()
	{
		if($this->session->userdata('User_id')!='' && $this->session->userdata('email')!='')
		{
			return true;
		}
		else
		{
			$this->session->sess_destroy();
			redirect();
		}
	}

	/**
	 * login fnction for this controller.
	 */
	public function login()
	{  
		$this->load->library('form_validation'); 
		$this->form_validation->set_rules('email', 'email', 'trim|required|valid_email|callback_checklogin');
		$this->form_validation->set_rules('password', 'password', 'trim|required');

		if ($this->form_validation->run() == FALSE)
		{ 
			$this->load->view('includes/header');
			$this->load->view('login');
			$this->load->view('includes/footer');

			return false; 
		}
		else
		{ 
			redirect();
		}
	}
	
	/**
	 * checklogin fnction for this controller.
	 */
	public function checklogin($str)
	{
		$email = strtolower($str);
		$password = $this->input->post('password');
		$password = CRC32($email.$password);
		
		$user = $this->commonmodel->getRecords('Real_Users','User_id',array("email"=>$str,"password "=>$password),'',true);
		if($user)
		{
			$this->session->set_userdata('email',$str);
			$this->session->set_userdata('User_id',$user['User_id']);
			return true;
		}
		else
		{
			$this->form_validation->set_message('checklogin', 'Invalid email or password.');
			return FALSE;
		}
	}
	
	/**
	 * logout fnction for this controller.
	 */
	public function logout()
	{ 
		$array_items = array('User_id' => '', 'email' => '');
		$this->session->unset_userdata();
		unset($this->session->userdata);  
		$this->session->sess_destroy();
		redirect();
	}

	/**
	 * home fnction for this controller.
	 */
	public function home()
	{
		$this->checkAuth();
		$this->load->view('includes/header');
		$this->load->view('home');
		$this->load->view('includes/footer');
	}

	/**
	 * property fnction for this controller.
	 */
	public function property()
	{
		$this->checkAuth();
		$this->load->view('includes/header');
		$this->load->view('basic');
		$this->load->view('includes/footer');
	}

	/**
	 * propertyList fnction for this controller.
	 */
	public function propertyList()
	{
		$this->checkAuth();
		$selection_id = $this->input->post('selection');
		$searchby = $this->input->post('searchby');
		switch($searchby)
		{
			case 1:
				$searchby = "Property_id";
				break;
			case 2:
				$searchby = "ListingNumber";
				break;
			case 3:
				$searchby = "ListingID";
				break;
		}
		$property = $this->commonmodel->getRecords('Real_Listing','',array($searchby=>$selection_id),'', true);
		//echo'<pre>';print_r($property);exit;
		if(!count($property))
		{
			echo "No data found";
			return;
		}
		$propertydata['property']=$property ;
		$this->session->set_userdata('property',$property);
		$this->load->view('propertylist', $propertydata);
	}

	/**
	 * listText fnction for this controller.
	 */
	public function listText()
	{
		$this->checkAuth();
		$selection_id = $this->input->post('selection');
		$searchby = $this->input->post('searchby');
		$textsource = "PUBLIC";
		switch($searchby)
		{
			case 1:
				$searchby = "Property_id";
				break;
			case 2:
				$searchby = "ListingNumber";
				break;
			case 3:
				$searchby = "ListingID";
				break;
		}
		$property = $this->commonmodel->getRecords('Real_Listing','Property_id',array($searchby=>$selection_id),'', true);
		if(!count($property))
		{
			echo "No data found";
			return;
		}

		$text = $this->commonmodel->getRecords('Real_Text','',array('Property_id'=>$property['Property_id'],'TextSource'=>$textsource),'', true);
		$textdata['text']=$text ;
		if(!count($text))
		{
			echo "No data found";
			return;
		}

		$this->load->view('text/listtext',$textdata);
		$this->realQwords($property['Property_id']);
	}

	/**
	 * RealText fnction for this controller.
	 */
	public function realText($property_id, $textsource="PUBLIC")
	{
		$this->checkAuth();
		$text = $this->commonmodel->getRecords('Real_Text','',array('Property_id'=>$property_id,'TextSource'=>$textsource),'', true);
		$textdata['text']=$text ;
		$this->load->view('text/realtext', $textdata);
	}

	/**
	 * realQwords fnction for this controller.
	 */
	public function realQwords($property_id)
	{
		$this->checkAuth();
		$qwords = $this->commonmodel->getRecords('Real_QWords','',array('Property_id'=>$property_id));
		$qwordsdata['qwords']=$qwords ;
		$this->load->view('text/qwords', $qwordsdata);
	}

	/**
	 * listLocation fnction for this controller.
	 */
	public function listLocation()
	{
		$this->checkAuth();
		$location_id ='';
		$selection_id = $this->input->post('selection');
		$searchby = $this->input->post('searchby');
		$textsource = "PUBLIC";
		switch($searchby)
		{
			case 1:
				$searchby = "Property_id";
				break;
			case 2:
				$searchby = "ListingNumber";
				break;
			case 3:
				$searchby = "ListingID";
				break;
		}
		$property = $this->commonmodel->getRecords('Real_Listing','Location_id',array($searchby=>$selection_id),'', true);
		if(empty($property))
		{
			echo "No data found";
			return;
		}

		if($property['Location_id']==null)
		{
			echo "No data found";
			return;
		}
		$location_id = $property['Location_id'];
		$location = $this->commonmodel->getRecords('Real_Location','',array('Location_id'=>$location_id),'', true);
		if(!count($location))
		{
			echo "No data found";
			return;
		}
		$location_data['location']=$location ;
		$location_data['location_listing'] = $this->commonmodel->getRecords('Location_Listing','',array('Location_id'=>$location_id),'', true);
		$location_data['polygon_listing'] = $this->commonmodel->getRecords('Real_Polygon','',array('Location_id'=>$location_id),'', true);
		$location_data['real_location'] = $this->listingmodel->getListStreet($location['StreetName'], 'StreetName');
		$this->load->view('location/locationinfo',$location_data);
		$this->load->view('location/locationnav',$location_data);
	}

	/**
	 * listStreet fnction for this controller.
	 */
	public function listStreet($keyword, $search_by="StreetName", $ft=0)
	{
		$this->checkAuth();
		$keyword =  urldecode ($keyword);
		$location_data['real_location'] = $this->listingmodel->getListStreet($keyword, $search_by, $ft);
		$this->load->view('location/streetlist',$location_data);
	}

	/**
	 * listOffice fnction for this controller.
	 */
	public function listOffice()
	{
		$this->checkAuth();
		$property = $this->session->userdata('property');
		$data['office'] = $this->commonmodel->getRecords('Real_Office','',array('Office_id'=>$property['ListOffice_id']),'', true);
		$data['agent'] = $this->commonmodel->getRecords('Real_Agent','',array('Agent_id'=>$property['ListAgent_id']),'', true);
		$this->load->view('office/officeinfo', $data);
		$this->load->view('office/find');
	}

	/**
	 * findOfficeOrAgent fnction for this controller.
	 */
	public function findOfficeOrAgent()
	{
		$this->checkAuth();
		$property = $this->session->userdata('property');
		$post_data = $this->input->post();
		$data['search_type'] = $post_data['search_type'];
		$search_field = $post_data['search_by'] == "name" ? $post_data['search_type']."Name" : "List".$post_data['search_type']."Id";
		$data['list'] = $this->commonmodel->getRecords('Real_'.$post_data['search_type'],'',array($search_field=>$post_data['search_keyword']));
		$this->load->view('office/findlist', $data);
	}

	/**
	 * processLog fnction for this controller.
	 */
	public function processLog()
	{
		$this->checkAuth();
		$data['processes'] = $this->commonmodel->getRecords('Process_Execution');
		$this->load->view('includes/header');
		$this->load->view('process/processlog',$data);
		$this->load->view('includes/footer');
	}

	/**
	 * statusProcess fnction for this controller.
	 */
	public function statusProcess()
	{	
		$this->checkAuth();
		$process = $this->input->post('process');		
		$status['statuslog'] = $this->listingmodel->getStatusProcess($process);
		$this->load->view('process/status',$status);
	}
	
	/**
	 * keyWordPhrase fnction for this controller.
	 */
	public function keyWordPhrase()
	{
		$this->checkAuth();
		$this->load->view('includes/header');
		$this->load->view('keyword/keywordphrase');
		$this->load->view('includes/footer');
		$this->session->set_userdata('redirect_url',uri_string());
	}

	/**
	 * keyWordPhrase fnction for this controller.
	 */
	public function keyResult()
	{
		$this->checkAuth();
		$qdna = $this->input->post('qdna');
		$keyword =$this->input->post('keyword');
		$find = $this->input->post('find');
		$condition = "$find like '%$keyword%' and QDNA like '$qdna%'";
		$data = $this->commonmodel->getRecords('Keyword_Phrase','',$condition);
		$phrase['results'] = $data;
		
		if(!count($data))
		{
			echo "No data found";
			return;
		}
		$this->load->view('keyword/keyresult',$phrase);
	}

	/**
	 * keyWordPhrase fnction for this controller.
	 */
	public function addEditKeyword()
	{ 
		$this->checkAuth();
		$keyword_id = $this->input->post('keyword_id');
		$keywordData['KeyWord_id']=$keyword_id;
		if(isset($keyword_id) && $keyword_id!="")
		{
			$keywordData['keyWord'] = $this->commonmodel->getRecords('Keyword_Phrase','',array('KeyWord_id'=>$keyword_id),'', true);
		}

		$keywordData['qdna_list'] = $this->commonmodel->getRecords('tb_QDNA');
		$this->load->view('keyword/add-edit-keyword',$keywordData);
	}

	/**
	 * keyWordPhrase fnction for this controller.
	 */
	public function saveKeyword($KeyWord_id)
	{ 
		$this->checkAuth();
		$post_data= $this->input->post();
		$token=$post_data['token'];
		$condition = "Token LIKE '$token' and Property_id NOT IN(SELECT Property_id FROM Keyword_Rerun) ";
		$property_id = $this->commonmodel->getRecords('Real_QWords','Property_id',$condition,'Property_id');
		$this->listingmodel->insertId('Keyword_Rerun',$property_id);
		$this->listingmodel->saveKeyword($post_data, $KeyWord_id);
		echo $msg = $KeyWord_id=="0" ? 'added' : 'updated';
	}

// function for deleting affiliates.
	function deleteKeyword()
	{ 
		$this->checkAuth();
		$KeyWord_id = $_POST['KeyWord_id'];
		if($KeyWord_id != '')
		{
			// deleting records from database
			$this->commonmodel->deleteRecords('Keyword_Phrase',array("KeyWord_id"=>$KeyWord_id));				
			echo"1";exit;
		}
		else
		{
			echo"0";exit;
		}			
	}

	public function users()
	{
		$this->checkAuth();
		$data['users'] = $this->commonmodel->getRecords('Real_Users');
		$data['msg'] = $this->session->userdata('msg');
		$this->session->set_userdata('msg', '');
		$this->load->view('includes/header');
		$this->load->view('user/users',$data);
		$this->load->view('includes/footer');
	}

	public function addEditUser()
	{ 
		$this->checkAuth();
		$user_id = $this->input->post('user_id');
		
		$userData['User_id']=$user_id;
		if(isset($user_id) && $user_id!="")
		{
			$userData['user'] = $this->commonmodel->getRecords('Real_Users','',array('User_id'=>$user_id),'', true);
		}
			
		$this->load->view('user/add-edit-user',$userData);
	}

	public function saveUser($User_id="")
	{ 
		$this->checkAuth();
		$post_data = $this->input->post();
		$this->listingmodel->saveUser($post_data, $User_id);
		$msg = $User_id=="" ? 'added' : 'updated';
		$this->session->set_userdata('msg', $msg);
		redirect('listing/users');
	}

	// function for deleting affiliates.
	function deleteUser()
	{ 
		$this->checkAuth();
		$User_id = $_POST['User_id'];
		if($User_id != '')
		{
			// deleting records from database
			$this->commonmodel->deleteRecords('Real_Users',array("User_id"=>$User_id));				
			echo"1";exit;
		}
		else
		{
			echo"0";exit;
		}			
	}

	public function checkUser($User_id='')
	{
		$uname=$this->input->post('uname');	
		$email=$this->input->post('email');

		if($uname !="")
		{
			$user = $this->commonmodel->getRecords('Real_Users','',array("Username"=>$uname),'',true);
			
			if($uname==$user['Username'] && $User_id != $user['User_id'])
			{
				echo 'user';				
			}
			else
			{
				echo 'success';
			}
		}

		if($email !="")
		{
			$user = $this->commonmodel->getRecords('Real_Users','',array("Email"=>$email),'',true);
			$mail=$user['Email'];
			if($email==$mail)
			{
				echo 'email';
			}
			else
			{
				echo 'success';
			}
		}
	}

	/**
	 *	realPhoto fnction for this controller.
	 */
	public function realPhoto()
	{
		$this->checkAuth();
		$selection_id = $this->input->post('selection');
		$searchby = $this->input->post('searchby');
		
		switch($searchby)
		{
			case 1:
				$searchby = "Property_id";
				break;
			case 2:
				$searchby = "ListingNumber";
				break;
			case 3:
				$searchby = "ListingID";
				break;
		}

		$captionData=array('Photo_id'=>'');

		$photoData = $this->commonmodel->getRecords('Real_Photo','ListingID,Folder,Location_id,PhotoCount',array($searchby=>$selection_id),'', true);
		if(!count($photoData))
		{
			echo "No data found";
			return;
		}

		$locationData = $this->commonmodel->getRecords('Real_Location','County',array('Location_id'=>$photoData['Location_id']),'', true);

		$county ='_'.$locationData['County'];
		$validCounty=array('Adams', 'Arapahoe', 'Boulder','Broomfield', 'ClearCreek','Denver', 'Douglas', 'Jefferson', 'Weld');
		if(in_array($locationData['County'],$validCounty))
		{
			$captionData = $this->commonmodel->getRecords('Location_Photo'.$county,'Caption,Photo_id,Seqno',array('Location_id'=>$photoData['Location_id']),'', true);
		}
		else
		{
			$captionData = $this->commonmodel->getRecords('Location_Photo','Caption,Photo_id,Seqno',array('Location_id'=>$photoData['Location_id']),'', true);
		}
		
		if(!empty($captionData)){
			$tagline = $this->commonmodel->getRecords('Public_Text','Comment,Text_id',array('Photo_id'=>$captionData['Photo_id']),'', true);
		}

		if(!count($photoData))
		{
			$photoData=array('ListingID'=>'','Folder'=>'','Location_id'=>'','PhotoCount'=>'##');
		}
		if(!count($captionData))
		{
			$captionData=array('Caption'=>'','Photo_id'=>'','Seqno'=>'0');
		}

		$data['photoData']=$photoData;
		$data['captionData']=$captionData;
		
		if(empty($tagline))
		{
			$tagline=array('Comment'=>'no');
			$data['tagline']=$tagline;
		}
		else
		{
			$data['tagline']=$tagline;
		}
		$this->load->view('photo/real_photo',$data);
	}

	/**
	 *	realPhoto fnction for this controller.
	 */
	public function realPhotoNext()
	{
		$this->checkAuth();
		$selection_id = $this->input->post('selection');
		$searchby = $this->input->post('searchby');
		$Seqno = $this->input->post('Seqno');
		$ListingID = $this->input->post('ListingID');

		switch($searchby)
		{
			case 1:
				$searchby = "Property_id";
				break;
			case 2:
				$searchby = "ListingNumber";
				break;
			case 3:
				$searchby = "ListingID";
				break;
		}

		$captionData=array('Photo_id'=>'');
		$photoData = $this->commonmodel->getRecords('Real_Photo','ListingID,Folder,Location_id,PhotoCount',array($searchby=>$selection_id),'', true);
		$locationData = $this->commonmodel->getRecords('Real_Location','County',array('Location_id'=>$photoData['Location_id']),'', true);
		$county ='_'.$locationData['County'];
		$validCounty=array('Adams', 'Arapahoe', 'Boulder','Broomfield', 'ClearCreek','Denver', 'Douglas', 'Jefferson', 'Weld');
		if(in_array($locationData['County'],$validCounty))
		{
			$captionData = $this->commonmodel->getRecords('Location_Photo'.$county,'Caption,Photo_id,Seqno',array('Location_id'=>$photoData['Location_id'],'Seqno'=>$Seqno),'', true);
		}
		else
		{
			$captionData = $this->commonmodel->getRecords('Location_Photo','Caption,Photo_id,Seqno',array('Location_id'=>$photoData['Location_id'],'Seqno'=>$Seqno),'', true);
		}
		if(!empty($captionData)){
			$tagline = $this->commonmodel->getRecords('Public_Text','Comment,Text_id',array('Photo_id'=>$captionData['Photo_id']),'', true);
		}

		if(!count($photoData))
		{
			$photoData=array('ListingID'=>'','Folder'=>'','Location_id'=>'','PhotoCount'=>'##');
		}
		if(!count($captionData))
		{
			$captionData=array('Caption'=>'','Photo_id'=>'','Seqno'=>'0');
		}
		
		$data['photoData']=$photoData;
		$data['captionData']=$captionData;

		if(empty($tagline))
		{
			$tagline=array('Comment'=>'no');
			$data['tagline']=$tagline;
		}
		else
		{
			$data['tagline']=$tagline;
		}
		$this->load->view('photo/real_photo',$data);
	}

	/**
	 *	tagline fnction for this controller.
	 */
	public function tagline()
	{
		$this->checkAuth();
		$selection_id = $this->input->post('selection');
		$searchby = $this->input->post('searchby');
		$Photoid = $this->input->post('Photo_id');
		$tag = $this->input->post('tag');
		$User_id = $this->session->userdata('User_id');
		$Image['Text_id']='';

		switch($searchby)
		{
			case 1:
				$searchby = "Property_id";
				break;
			case 2:
				$searchby = "ListingNumber";
				break;
			case 3:
				$searchby = "";
				break;
		}


		$Property_id = $this->commonmodel->getRecords('Real_Photo','Property_id',array($searchby=>$selection_id),'', true);
		$Image = $this->commonmodel->getRecords('Public_Text','Photo_id,Text_id',array('Photo_id'=>$Photoid),'', true);

		if(empty($Image))
		{
			$post_data['Photo_id']=$Photoid;
			$post_data['tag']=$tag;
			$post_data['User_id']=$User_id;
			$post_data['Property_id']=$Property_id['Property_id'];
			$post_data['find']=0;
			//$this->listingmodel->saveTag($post_data);
		}
		else
		{
			$post_data['Photo_id']=$Photoid;
			$post_data['tag']=$tag;
			$post_data['find']=1;
			//$this->listingmodel->saveTag($post_data);
		}
		$this->listingmodel->saveTag($post_data);
		echo $msg = $post_data['find']==0 ? 'added' : 'updated';
	}

	/**
	 * property fnction for this controller.
	 */
	public function process_execution()
	{
		$this->checkAuth();
		$this->load->helper('date'); 
		$this->load->view('includes/header');
		$property = $this->listingmodel->getRecords('Process_Schedule','Process_Execution');
		$data['properties']=$property;
		$this->load->view('process-execution',$data);
		$this->load->view('includes/footer');
	}

	public function areaNamesMaintenance()
	{
		$this->checkAuth();
	
		$sql = "SELECT t.Area_id, t.Neighborhood, c.Count FROM tb_Neighborhood t, tb_Neighborhood_County c WHERE t.Area_id=c.Area_id AND t.Neighborhood LIKE 'a%' ORDER BY t.Neighborhood ASC";
		$query = $this->db->query($sql);
		$data['area_name'] = $query->result_array();
		$this->load->view('includes/header');
		$this->load->view('area-name-maintenance/area_name',$data);
		$this->load->view('includes/footer');
	}

	public function areaNamesDisplay()
	{
		$this->checkAuth();
		$like= $this->input->post('like');
		if ($like==''){
			$like='a';
		}
		$condition="LIKE '$like%'";
		if ($like=='#'){
			$condition="REGEXP '^[0-9]'";
		}

		$sql = "SELECT t.Area_id, t.Neighborhood, c.Count FROM tb_Neighborhood t, tb_Neighborhood_County c WHERE t.Area_id=c.Area_id AND t.Neighborhood $condition ORDER BY t.Neighborhood ASC";
		$query = $this->db->query($sql);
		$data['area_name'] = $query->result_array();
		$this->load->view('area-name-maintenance/area_name_display',$data);
	}

	public function addEditAreaNames()
	{ 
		$this->checkAuth();
		$userData['Area_Name']['Area_id']=$this->input->post('Area_id');
		$userData['Area_Name']['Neighborhood']= $this->input->post('Neighborhood');
		$userData['Area_Name']['Count']=$this->input->post('Count');
		$this->load->view('area-name-maintenance/add_edit_area_name',$userData);
	}

	public function saveAreaNames()
	{ 
		$this->checkAuth();
		if($this->input->post('save')=="Save")
		{
			$Area_id = $this->input->post('Area_id');
			$Neighborhood = $this->input->post('AreaName');
			$Count = $this->input->post('Count');

			$this->listingmodel->updateAreaNames('tb_Neighborhood',array("Neighborhood"=>$Neighborhood),"Area_id=".$Area_id);
			if($Count>0)
			{
				$this->listingmodel->updateAreaNames('Polygon_Neighborhood',array("Neighborhood"=>$Neighborhood),"Area_id =".$Area_id);
			}
			if($Count<0)
			{
				$this->listingmodel->updateAreaNames('Real_Location',array("Area_id"=>0,"AreaName"=>$Neighborhood),"Area_id =".$Area_id);
				$this->listingmodel->updateAreaNames('Real_Location',array("Sub_id"=>0,"Subdivision"=>$Neighborhood),"Sub_id =".$Area_id);
			}
		}
	}

	// function for deleting affiliates.
	function deleteAreaName()
	{ 
		$this->checkAuth();
		$Area_id = $_POST['Area_id'];
		$AreaName = $_POST['Neighborhood'];
		if($Area_id != '')
		{
			// deleting records from database
			$this->commonmodel->deleteRecords('Polygon_Neighborhood',array("Area_id"=>$Area_id));
			$this->commonmodel->deleteRecords('tb_Neighborhood',array("Area_id"=>$Area_id));
			$this->listingmodel->updateAreaNames('Real_Location',array("Area_id"=>0,"AreaName"=>$AreaName),"Area_id =".$Area_id);
			$this->listingmodel->updateAreaNames('Real_Location',array("Sub_id"=>0,"Subdivision"=>$AreaName),"Sub_id =".$Area_id);
			echo "1";
		}
		else
		{
			echo"0";
		}			
	}
	/***************************************** Find More Like *************************************/
	function more_like()
	{ 
		$this->checkAuth();
		$this->load->view('includes/header');
		$this->load->view('more-like/more_like');
		$this->load->view('includes/footer');
	}

	public function more_like_list()
	{
		$this->checkAuth();
		$selection_id = $this->input->post('selection');
		$searchby = $this->input->post('searchby');
		switch($searchby)
		{
			case 1:
				$searchby = "Property_id";
				break;
			case 2:
				$searchby = "ListingNumber";
				break;
			case 3:
				$searchby = "ListingID";
				break;
		}
		
		$sql="SELECT r.Property_id, l.Location_id, r.ListingId, r.ListingNumber, r.PropertyType, r.ListPrice, r.SquareFeet, r.TotalBedrooms , r.TotalBathrooms , l.geodna FROM Real_Listing r, Real_Location l WHERE r.Location_id = l.Location_id AND r.Status IN ('A', 'U') AND l.Active =1 AND $searchby=$selection_id";
		$query=$this->db->query($sql);
		$property = $query->row_array();
		//echo "<pre>"; print_r($property);//exit;
		if(count($property))
		{
			$geodna=$property['geodna'];
			/*$property = $this->commonmodel->getRecords('Real_Listing','Property_id,Location_id,ListingId,ListingNumber,PropertyType,ListPrice,SquareFeet,TotalBedrooms,TotalBathrooms', array($searchby=>$selection_id),'', true);
			//echo "<pre>"; print_r($property);exit;
			if(count($property))
			{
				$Real_location = $this->commonmodel->getRecords('Real_Location','geodna', array('Location_id'=>$property['Location_id']),'', true);

				if(!count($Real_location))
				{
					echo "No data found";
					return;
				}
				else
				{
					$geodna=$Real_location['geodna'];
				}
			}
			else 
			{
				if(!count($property))
				{
				echo "No data found";
				return;
				}
			}*/
			$garagespaces = $this->commonmodel->getRecords('Location_Parking','CarStorage,AttachedSpaces,DetachedSpaces,RecVehicleSpaces',array('Location_id'=>$property['Location_id']),'', true);
			
			if(!count($garagespaces))
				$garagespaces['garspace']=0;
			else
				$garagespaces['garspace']=$garagespaces['AttachedSpaces']+ $garagespaces['DetachedSpaces']+ $garagespaces['RecVehicleSpaces'];

			// Delete data from MoreLike_Base table...
			$this->commonmodel->deleteRecords('MoreLike_Base',array('Property_id'=>$property['Property_id']));
			
			//Step 0: Gather data for base property
			$Polygon_id = $this->commonmodel->getRecords('Real_Polygon','Polygon_id',array('Location_id'=>$property['Location_id']),'', True); 
			$Polygon_Stats_data=array();
			if(count($Polygon_id))
			{
				$Polygon_Stats_data = $this->commonmodel->getRecords('Polygon_Stats','AveragePrice,MedianPrice, AveragePriceSqft, MedianPriceSqft',array('Polygon_id'=>$Polygon_id['Polygon_id'],'PropertyType'=>$property['PropertyType']),'', True);
			}
			
			if(count($Polygon_Stats_data))
			{
				$property ['AveragePrice']			=	$Polygon_Stats_data['AveragePrice'];
				$property ['MedianPrice']			=	$Polygon_Stats_data['MedianPrice'];
				$property ['AveragePriceSqft']	=	$Polygon_Stats_data['AveragePriceSqft'];
				$property ['MedianPriceSqft']	=	$Polygon_Stats_data['MedianPriceSqft'];
			}
			else
			{
				$property ['AveragePrice']			=	0;
				$property ['MedianPrice']			=	0;
				$property ['AveragePriceSqft']	=	0;
				$property ['MedianPriceSqft']	=	0;
			}
			
			//insert data in to MoreLike_Base table...
			$data=array (
					'Property_id'		=>	$property['Property_id'],
					'MLS'					=>	$property['ListingNumber'],
					//'ListingId'=>$property['ListingId'],
					'PropertyType'	=>	$property['PropertyType'],
					'ListPrice'			=>	$property['ListPrice'],
					'SquareFeet'		=>	$property['SquareFeet'],
					'Bedrooms'		=>	$property['TotalBedrooms'],
					'Bathrooms'		=>	$property['TotalBathrooms'],
					'GarageSpaces'	=>	$garagespaces['garspace']
				);
			$this->listingmodel->commonInsertUpdate('MoreLike_Base',$data,'');

			//Get the QDNA and JUMP Term from Real_QWords
			
			//$sql="SELECT QDNA, Token, QWords FROM Real_QWords WHERE ".$searchby."=".$selection_id;
			$sql="SELECT QDNA, Token, QWords FROM Real_QWords WHERE Property_id=  ".$property['Property_id'];
			$query=$this->db->query($sql);
			$token = $query->result_array();
			$property ['token']=$token;

			$property ['geodna']=$geodna;
			$property ['garagespaces']=$garagespaces['garspace'];
			$morelikedata['property']=$property ;
			$this->load->view('more-like/morelikelist',$morelikedata);
		}
		else 
		{
			echo "No data found";
			return;
		}
	}

	function delete_morelisting()
	{ 
		$this->checkAuth();
		//echo '<pre>'; print_r($this->input->post());exit;
		$property_id				= $this->input->post('property_id');
		$Location_id				= $this->input->post('Location_id');
		$PropertyType			= $this->input->post('PropertyType');
		$PricePerFoot			= $this->input->post('PricePerFoot');
		$MinPrice					= $this->input->post('MinPrice');
		$MaxPrice					= $this->input->post('MaxPrice');
		$MinPricePerFt			= $this->input->post('MinPricePerFt');
		$MaxPricePerFt		= $this->input->post('MaxPricePerFt');
		$MinSqft						= $this->input->post('MinSqft');
		$MaxSqft					= $this->input->post('MaxSqft');
		$MinBed					= $this->input->post('MinBed');
		$MaxBed					= $this->input->post('MaxBed');
		$MinBath					= $this->input->post('MinBath');
		$MaxBath					= $this->input->post('MaxBath');
		$Bedrooms				= $this->input->post('Bedrooms');
		$Bathrooms				= $this->input->post('Bathrooms');
		$Base_Geodna			= $this->input->post('Geodna');
		$GarageSpaces			= $this->input->post('GarageSpaces');
		$geodna_length		=	strlen($Base_Geodna);
		
		$MoreLike_ListingsRecords = $this->commonmodel->getRecords('MoreLike_Listings','Like_id,qProp_id',array('property_id'=>$property_id),'', false);
		//echo '<pre>';print_r($MoreLike_ListingsRecords);exit;
		if(count($MoreLike_ListingsRecords))
		{
			foreach($MoreLike_ListingsRecords as $MoreLike_ListingsRecord)
			{
				$Like_id=$MoreLike_ListingsRecord['Like_id'];
				$this->commonmodel->deleteRecords('MoreLike_QWords',array('Like_id'=>$Like_id));
			}
			$this->commonmodel->deleteRecords('MoreLike_Listings',array('property_id'=>$property_id));
		}
		
		// $sql="INSERT INTO MoreLike_Listings (Property_id, qProp_id, MLS, Polygon_id, ListPrice, SquareFeet, PricePerFoot, Bedrooms, Bathrooms, Latitude, Longitude, geodna)(SELECT ".$property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND r.Status IN ('A','U')  AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice." AND  r.SquareFeet BETWEEN ".$MinSqft." AND ".$MaxSqft." AND  r.TotalBedrooms BETWEEN ".$MinBed." AND ".$MaxBed." AND  r.TotalBathrooms BETWEEN ".$MinBath." AND ".$MaxBath.")";
		// $this->db->query($sql);


		
		$sql="SELECT ".$property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND  r.Status IN ('A','U') AND l.Active=1 AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice." AND  r.SquareFeet BETWEEN ".$MinSqft." AND ".$MaxSqft." AND  r.TotalBedrooms BETWEEN ".$MinBed." AND ".$MaxBed." AND  r.TotalBathrooms BETWEEN ".$MinBath." AND ".$MaxBath;
				
		if ($this->db->query($sql)->num_rows() == 0)
		{
			$MinPrice			= round($ListPrice -($ListPrice*0.40));
			$MaxPrice			= round($ListPrice + ($ListPrice*0.40));
			$MinSqft				= round($SquareFeet - ($SquareFeet*0.40));
			$MaxSqft			= round($SquareFeet + ($SquareFeet*0.40));

			$sql="SELECT ".$property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND  r.Status IN ('A','U') AND l.Active=1 AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice." AND  r.SquareFeet BETWEEN ".$MinSqft." AND ".$MaxSqft." AND  r.TotalBedrooms BETWEEN ".$MinBed." AND ".$MaxBed." AND  r.TotalBathrooms BETWEEN ".$MinBath." AND ".$MaxBath;

			if($this->db->query($sql)->num_rows() ==0)
			{
				$sql="SELECT ".$property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND  r.Status IN ('A','U') AND l.Active=1 AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice." AND  r.SquareFeet BETWEEN ".$MinSqft." AND ".$MaxSqft;

				if($this->db->query($sql)->num_rows()==0)
				{
					$sql="SELECT ".$property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND  r.Status IN ('A','U') AND l.Active=1 AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice;
					
					if($this->db->query($sql)->num_rows()==0)
					{
						trace("No matching record found for Property_id=$Property_id");
					}
					else
					{
						$sql="INSERT INTO MoreLike_Listings (Property_id, qProp_id, MLS, Polygon_id, ListPrice, SquareFeet, PricePerFoot, Bedrooms, Bathrooms, Latitude, Longitude, geodna)(SELECT ".$property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND  r.Status IN ('A','U') AND l.Active=1 AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice.")";
						$this->db->query($sql);
					}
				}
				else
				{
					$sql="INSERT INTO MoreLike_Listings (Property_id, qProp_id, MLS, Polygon_id, ListPrice, SquareFeet, PricePerFoot, Bedrooms, Bathrooms, Latitude, Longitude, geodna)(SELECT ".$property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND  r.Status IN ('A','U') AND l.Active=1 AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice." AND  r.SquareFeet BETWEEN ".$MinSqft." AND ".$MaxSqft.")"; 
					$this->db->query($sql);
				}
			}
			else
			{
				$sql="INSERT INTO MoreLike_Listings (Property_id, qProp_id, MLS, Polygon_id, ListPrice, SquareFeet, PricePerFoot, Bedrooms, Bathrooms, Latitude, Longitude, geodna)(SELECT ".$property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND  r.Status IN ('A','U') AND l.Active=1 AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice." AND  r.SquareFeet BETWEEN ".$MinSqft." AND ".$MaxSqft." AND  r.TotalBedrooms BETWEEN ".$MinBed." AND ".$MaxBed." AND  r.TotalBathrooms BETWEEN ".$MinBath." AND ".$MaxBath.")"; 
				$this->db->query($sql);
			}
		}
		else
		{
			$sql="INSERT INTO MoreLike_Listings (Property_id, qProp_id, MLS, Polygon_id, ListPrice, SquareFeet, PricePerFoot, Bedrooms, Bathrooms, Latitude, Longitude, geodna)(SELECT ".$property_id.", r.Property_id, r.ListingNumber as MLS, p.Polygon_id, r.ListPrice, r.SquareFeet, ROUND(r.ListPrice/r.SquareFeet,0), r.TotalBedrooms as Bedrooms, r.TotalBathrooms as Bathrooms, l.Latitude, l.Longitude, l.geodna FROM Real_Listing  r, Real_Polygon p,  Real_Location l WHERE r.Location_id = p.Location_id  AND r.Location_id = l.Location_id AND r.`PropertyType`='".$PropertyType."' AND  r.Status IN ('A','U') AND l.Active=1 AND  r.ListPrice BETWEEN ".$MinPrice." AND ".$MaxPrice." AND  r.SquareFeet BETWEEN ".$MinSqft." AND ".$MaxSqft." AND  r.TotalBedrooms BETWEEN ".$MinBed." AND ".$MaxBed." AND  r.TotalBathrooms BETWEEN ".$MinBath." AND ".$MaxBath.")"; 
			$this->db->query($sql);
		}	
		
		
		/******************************************/
		//get max_lilsting
		$SystemSettingsmax = $this->commonmodel->getRecords('SystemSettings','KeyValue',array('Keyname'=>'MORELIKE','subKeyname'=>'MAX_LISTINGS'),'', True);
		$max_listing=$SystemSettingsmax['KeyValue'];
		
		//get min_lilsting
		$SystemSettingsmin = $this->commonmodel->getRecords('SystemSettings','KeyValue',array('Keyname'=>'MORELIKE','subKeyname'=>'MIN_LISTINGS'),'', True);
		$min_listing=$SystemSettingsmin['KeyValue'];
		

		$sql="SELECT COUNT( * ) AS Count FROM MoreLike_Listings WHERE Property_id =".$property_id;
		$query=$this->db->query($sql);
		$listings = $query->row_array();
		$total_listing_count=$listings['Count'];
		//echo '<pre>';print_r($listings);exit;
		$concat_limit=5;
		$count=0;
		$morelike_records_array=array();
		do
		{
			$sql="SELECT COUNT(*) AS Count FROM MoreLike_Listings WHERE Property_id =". $property_id ." AND geodna LIKE concat(left('".$Base_Geodna."',".$concat_limit."),'%')";
			$query=$this->db->query($sql);
			$listings = $query->row_array();
			
			$matched_listing_count=$listings['Count'];
			if($matched_listing_count>=$max_listing)
			{
					$concat_limit++;
			}
		}
		//while($matched_listing_count>$min_listing || $matched_listing_count<$max_listing && $concat_limit<$geodna_length);
		while($matched_listing_count>=$max_listing && $concat_limit<$geodna_length);
		
		// delete all the records that does no match with the base property
		$sql="SELECT Like_id FROM MoreLike_Listings WHERE Property_id =". $property_id ." AND geodna LIKE concat(left('".$Base_Geodna."',".$concat_limit."),'%') ";
		$query=$this->db->query($sql);
		$morelike_records = $query->result_array();
	
		foreach($morelike_records as $morelike_record)
		{
			$morelike_records_array[]=$morelike_record['Like_id'];
		}
		//echo '<pre>';print_r($morelike_records_array);//exit;
		$sql="SELECT Like_id FROM MoreLike_Listings WHERE Property_id =". $property_id;
		$query=$this->db->query($sql);
		$morelike_results = $query->result_array();
		//echo '<pre>';print_r($morelike_results);exit;
		//	$cnt=0;
		foreach($morelike_results as $morelike_result)
		{
			if(!in_array($morelike_result['Like_id'],$morelike_records_array))
			{
				$sql="DELETE FROM MoreLike_Listings WHERE Property_id=".$property_id." AND Like_id=".$morelike_result['Like_id'];
				$this->db->query($sql);
			}

		}
		/*****************************************/
		//echo $cnt;exit;
		/*********step 5************/
		
		$SystemSettings = $this->commonmodel->getRecords('SystemSettings','KeyValue',array('Keyname'=>'MORELIKE','subKeyname'=>'PROXIMITY'),'', True);
		//echo $SystemSettings['KeyValue']; 
		$Base_Location_id= $this->commonmodel->getRecords('Real_Listing','Location_id',array('Property_id'=>$property_id),'', true);

		$Base_lat_long= $this->commonmodel->getRecords('Real_Location','Location_id,Latitude,Longitude',array('Location_id'=>$Base_Location_id['Location_id']),'', true);
		$lat1=$Base_lat_long['Latitude'];
		$lon1=$Base_lat_long['Longitude'];

		$qProp_ids = $this->commonmodel->getRecords('MoreLike_Listings','qProp_id',array('Property_id'=>$property_id),'', false);
		//echo '<pre>';print_r($qProp_ids);//exit;
		foreach($qProp_ids as $qProp_id)
		{
			$Location_id= $this->commonmodel->getRecords('Real_Listing','Location_id',array('Property_id'=>$qProp_id['qProp_id']),'', true);
			$lat_long= $this->commonmodel->getRecords('Real_Location','Location_id,Latitude,Longitude',array('Location_id'=>$Location_id['Location_id']),'', true);
			//echo '<pre>';print_r($lat_long);exit;
			$lat2=$lat_long['Latitude'];
			$lon2=$lat_long['Longitude'];
			$unit='M';
			$distance=$this->distance($lat1, $lon1, $lat2, $lon2, $unit) ;
			
			/********************************************************/
			if($distance > $SystemSettings['KeyValue'])
			{
				$this->commonmodel->deleteRecords('MoreLike_Listings',array("Property_id"=>$property_id,"qProp_id"=>$qProp_id['qProp_id']));				
			}
			else
			{
				$data=array('Proximity'=>$distance);
				$this->listingmodel->commonInsertUpdate('MoreLike_Listings',$data,"qProp_id=".$qProp_id['qProp_id']);
			}
			
			/***********************************************************/
			
			//$data=array('Proximity'=>$distance);
			//$this->listingmodel->commonInsertUpdate('MoreLike_Listings',$data,"qProp_id=".$qProp_id['qProp_id']. " AND Property_id=".$property_id);
		} 

	

	/************************************************/	
		//Step 2: Work with the above listings stored in MoreLike_Listings table
		
		$MoreLike_ListingsRecords = $this->commonmodel->getRecords('MoreLike_Listings','Like_id,qProp_id',array('property_id'=>$property_id),'', false);

		foreach($MoreLike_ListingsRecords as $MoreLike_ListingsRecord)
		{
			// echo "<pre>"; print_r($MoreLike_ListingsRecord); exit;
			$Like_id=$MoreLike_ListingsRecord['Like_id'];
			$qProp_id=$MoreLike_ListingsRecord['qProp_id'];
			$sql="SELECT QDNA, Token, QWords, IF(QDNA='JUMP',1,0) AS Jump FROM Real_QWords WHERE Property_id =  ".$property_id." ORDER BY Jump DESC";
			$query=$this->db->query($sql);
			$token = $query->result_array();

			// Locate & Insert the QDNA data by substituting the token# into the command below 
			foreach ($token as $row)
			{
				//echo "<pre>"; print_r($row); exit;
				$Token=$row['Token'];
				if($row['Jump'] == 1)
				{
					$QDNA="t.QDNA='JUMP'";
					$sql="INSERT INTO MoreLike_QWords (Like_id,QDNA,Token,QWords,Rank,Weight)
							SELECT m.Like_id,q.QDNA,q.Token,q.QWords,t.Rank,t.Weight
							FROM MoreLike_Listings m, Real_QWords q, tb_QDNA t
							WHERE m.qProp_id = q.Property_id and q.Property_id = ".$qProp_id." and q.QDNA = t.QDNA AND ".$QDNA." and q.Token = ".$Token;
					$this->db->query($sql);
				}
				else
				{
					$token_exist = $this->commonmodel->getRecords('MoreLike_QWords','Like_id',array('Like_id'=>$Like_id,'QDNA'=>$row['QDNA'],'QWords'=>$row['QWords'], 'Token'=>$Token),'', true);
					//echo '<pre>';print_r($token_exist);
					
					if(!count($token_exist))
					{
						$QDNA="t.QDNA<>'JUMP'"; 
						$sql="INSERT INTO MoreLike_QWords (Like_id,QDNA,Token,QWords,Rank,Weight)
									SELECT m.Like_id,q.QDNA,q.Token,q.QWords,t.Rank,t.Weight
									FROM MoreLike_Listings m, Real_QWords q, tb_QDNA t
									WHERE m.qProp_id = q.Property_id and q.Property_id = ".$qProp_id." and q.QDNA = t.QDNA AND ".$QDNA." and q.Token = ".$Token;
						$this->db->query($sql);
					}
				}
			}
			// Total the Jump Scores
			$sql='UPDATE MoreLike_Listings l, 
						(SELECT q.Like_id, q.QDNA, SUM(q.Rank+q.Weight) as JumpScore 
						FROM MoreLike_QWords q 
						WHERE q.QDNA = "JUMP" and Like_id='.$Like_id.' group by q.Like_id, q.QDNA) as s 
						SET l.JumpScore = s.JumpScore 
						WHERE l.qProp_id ='. $qProp_id;
			$this->db->query($sql); 
			
			// Total the Q-Value Scores
			$sql='UPDATE MoreLike_Listings l, 
						(SELECT q.Like_id, q.QDNA, SUM(q.Rank+q.Weight) as ValueScore 
						 FROM MoreLike_QWords q 
						 WHERE q.QDNA <> "JUMP" and Like_id='.$Like_id.' group by q.Like_id) as s
						SET l.ValueScore = s.ValueScore
						WHERE l.qProp_id ='. $qProp_id;
			$this->db->query($sql); 
		}

		/****************************calculate perfectscore for base property*****************************/

		$more_like_id = $this->commonmodel->getRecords('MoreLike_Listings','Like_id',array('Property_id'=>$property_id),'', true);
		//echo '<pre>';print_r($more_like_id); exit;
		if(count($more_like_id))
		{
			$morelikeqworddata = $this->commonmodel->getRecords('MoreLike_QWords','',array('Like_id'=>$more_like_id['Like_id']),'', false);
			
			$perfectscore=0;
			foreach($morelikeqworddata as $morelikeqwordrow)
			{
				$perfectscore = $perfectscore + ( $morelikeqwordrow['Rank'] + $morelikeqwordrow['Weight']);
			}
			
			$data_array=array('PerfectScore'=>$perfectscore);
			//print_r($data_array); exit;
			$this->listingmodel->commonInsertUpdate('MoreLike_Base',$data_array,"Property_id=".$property_id);
		}
		
		/*******************************calculation for perfect score end***************************/
		
	/*
	*Step 3: Match the Price per Foot
	*/
		$morelikelistdata_array = $this->commonmodel->getRecords('MoreLike_Listings','',array('Property_id'=>$property_id),'', false);
		//echo '<pre>';print_r($morelikelistdata_array);//exit;
		foreach($morelikelistdata_array as $morelikelistdata_row)
		{
			if($morelikelistdata_row['PricePerFoot']>$MinPricePerFt && $morelikelistdata_row['PricePerFoot']<$MaxPricePerFt)
			{
				$BasePerFt = 0;
			}
			elseif($morelikelistdata_row['PricePerFoot']<=$MinPricePerFt)
			{
				$BasePerFt = -1;
			}
			elseif($morelikelistdata_row['PricePerFoot']>$MaxPricePerFt)
			{
				$BasePerFt = 1;
			}
			$sql="UPDATE MoreLike_Listings SET BasePerFt = ".$BasePerFt ." WHERE `qProp_id` = ". $morelikelistdata_row['qProp_id']." AND `Property_id`=". $property_id;
			$this->db->query($sql);
		}

	/* 
	*step 3 end
	*/

		$sql="SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id. " ORDER BY `qProp_id` ASC";
		$query=$this->db->query($sql);
		//echo '<pre>';print_r($query->result_array());exit;
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$result['qProp_id']=$row['qProp_id'];
				$result['SquareFeet']=$row['SquareFeet'];
				//echo "<hr />";
				$result['Bedrooms']=0;
				$result['Bathrooms']=0;
				//echo "<br />";
				//echo "Prop Bedrooms ".$row['Bedrooms'];
				//echo "<br />";
				//echo "Prop Bathrooms ".$row['Bathrooms'];
				//echo "<br />";

				if($row['Bedrooms']==$Bedrooms)
				{
					//echo "equal";
					$result['Bedrooms']=0;
				}
				else
				{
					if($row['Bedrooms']<$Bedrooms)
					{
						if($Bedrooms<=4)
							$result['Bedrooms']=-3;
						if($Bedrooms<=3)
							$result['Bedrooms']=-5;
						if($Bedrooms<=2)
							$result['Bedrooms']=-8;
					}
					else
					{
						if($Bedrooms==1)
							$result['Bedrooms']=8;
						if($Bedrooms==2)
							$result['Bedrooms']=5;
						if($Bedrooms==3)
							$result['Bedrooms']=3;
						if($Bedrooms>3)
							$result['Bedrooms']=1;
					}
				}
					
				
				if($row['Bathrooms']==$Bathrooms)
				{
					$result['Bathrooms']=0;
				}
				else
				{
					if($row['Bathrooms']<$Bathrooms)
					{
						if($Bathrooms<=3)
							$result['Bathrooms']=-5;
						if($Bathrooms==2)
							$result['Bathrooms']=-10;
					}
					else
					{
						if($Bathrooms==1)
							$result['Bathrooms']=8;
						if($Bathrooms==2)
							$result['Bathrooms']=5;
						if($Bathrooms>2)
							$result['Bathrooms']=2;
					}
				}
				

				$result['JumpScore']=ABS($row['JumpScore']);
				$result['ValueScore']=ABS($row['ValueScore']);

				/****************/

				//Calculate Proximity 
				$Proximity=ROUND($SystemSettings['KeyValue']/max($row['Proximity'],1000),0);
				$result['Proximity']=$Proximity;
				
				/*******************/
				
				if($row['BasePerFT']==0)
				{
					$result['BasePerFT']=2;
				}
				else
				{
					$result['BasePerFT']=0;
				}


				//echo "<pre>";print_r($result); 
				$Total_Score = $result['Bedrooms'] + $result['Bathrooms'] + $result['JumpScore'] + $result['ValueScore'] + $result['Proximity'] + $result['BasePerFT'];
				//$result['Score']=$Total_Score;

				//$data_array=array('JumpScore'=>$result['JumpScore'],'ValueScore'=>$result['ValueScore'],'Score'=>$result['Score']);
				$data_array=array('Score'=>$Total_Score);
				$this->listingmodel->commonInsertUpdate('MoreLike_Listings',$data_array,"qProp_id=".$result['qProp_id']);
			}
		}

		$baselike_score = $this->commonmodel->getRecords('MoreLike_Listings','Score',array('Property_id'=>$property_id, 'qProp_id'=>$property_id),'', True);
		//echo '<pre>';print_r($baselike_score);exit;
		//Calculate the MatchPercent
		if(count($baselike_score))
		{
			$sql="SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id;
			$query=$this->db->query($sql);
			//echo '<pre>';print_r($query->result_array());exit;
			if ($query->num_rows() > 0)
			{
				foreach ($query->result_array() as $row)
				{
					//echo '<pre>';print_r($row);
					$sql="SELECT Score  FROM MoreLike_Listings WHERE Property_id = ".$property_id." AND qProp_id = ".$row['qProp_id'];
					$query=$this->db->query($sql);
					$MoreLike_scoredata = $query->row_array();
					//echo '<pre>';print_r($MoreLike_scoredata);exit;
					if($baselike_score['Score'])
					{
						$MatchPercent=ROUND(($MoreLike_scoredata['Score']/$baselike_score['Score'])*100);
						
						$sql="UPDATE MoreLike_Listings 
						SET MatchPercent = ".$MatchPercent."
						WHERE Property_id = ".$property_id."
						AND qProp_id = ".$row['qProp_id'];
						$this->db->query($sql);
					}
				}
			}
		
			// calaculate maximum listing Limit
			$System_Settings = $this->commonmodel->getRecords('SystemSettings','KeyValue',array('Keyname'=>'MORELIKE','subKeyname'=>'MAX_LISTINGS'),'', True);
			$maxlisting=$System_Settings['KeyValue'];

			$sql="SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id." ORDER BY `Score`DESC";
			$sql_query=$this->db->query($sql);
			//echo '<pre>'; print_r($sql_query->result_array());
			$total_listing=count($sql_query->result_array());

			// Compare total listing (retrieve by morelike_listing) by maxlisting (maximum listing Limit)
			if($total_listing > $maxlisting)
			{
				$sql="SELECT * FROM (SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id." ORDER BY `Proximity` DESC LIMIT ".$maxlisting.") as temp ORDER BY `Score` DESC";
				$sql_query=$this->db->query($sql);
			}
			$_data['property']= $sql_query->result_array();
			$this->load->view('more-like/morelikeresult',$_data);
		}
		else 
		{
			$sql="DELETE FROM MoreLike_Listings WHERE Property_id = ". $property_id ; 
			$this->db->query($sql);
			$_data['property']= "No Match Found";
			$this->load->view('more-like/morelikeresult',$_data);
		}
	}
	/*
	*step 4 code
	*/
	function Garage_space()
	{
		$this->checkAuth();
		$Bedrooms = $this->input->post('Bedrooms');
		$Bathrooms = $this->input->post('Bathrooms');
		$property_id = $this->input->post('property_id');
		$GarageSpaces = $this->input->post('GarageSpaces');

		$qProp_ids = $this->commonmodel->getRecords('MoreLike_Listings','qProp_id',array('Property_id'=>$property_id),'', false);
		//echo '<pre>'; print_r($qProp_ids);exit;

		foreach($qProp_ids as $qProp_id)
		{
			$Location_id= $this->commonmodel->getRecords('Real_Listing','Location_id',array('Property_id'=>$qProp_id['qProp_id']),'', true);
			$garage_spaces= $this->commonmodel->getRecords('Location_Parking','Location_id,AttachedSpaces,DetachedSpaces,RecVehicleSpaces',array('Location_id'=>$Location_id['Location_id']),'', true);
			if(!count($garage_spaces))
			{
				$garagespace=0;
			}
			else
			{
				$garagespace=$garage_spaces['AttachedSpaces']+$garage_spaces['DetachedSpaces']+$garage_spaces['RecVehicleSpaces'];
				$data=array('GarageSpaces'=>$garagespace);
				$this->listingmodel->commonInsertUpdate('MoreLike_Listings',$data,"qProp_id=".$qProp_id['qProp_id']);
			}			
		}
		
		$sql="SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id." ORDER BY `Score`DESC";
		$query=$this->db->query($sql);
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$result['qProp_id']=$row['qProp_id'];
				$result['GarageSpaces']=$row['GarageSpaces'];
				if($GarageSpaces>0)
				{
					if($result['GarageSpaces']>=$GarageSpaces)
					{
						$row['Score']=$row['Score']+3;
					}
				}
				else
				{
					$row['Score']=$row['Score']+0;
				}
				$result['Score']=$row['Score'];
				$data['property'][]=$result;
				$data=array('Score'=>$result['Score']);
				$this->listingmodel->commonInsertUpdate('MoreLike_Listings',$data,"qProp_id=".$result['qProp_id']);
			}

			/*$sql="SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id." ORDER BY `Score`DESC";
			$sql_query=$this->db->query($sql);
			$data['property'] = $sql_query->result_array();
			$this->load->view('more-like/morelikeresult',$data);*/

			// calaculate maximum listing Limit
			$System_Settings = $this->commonmodel->getRecords('SystemSettings','KeyValue',array('Keyname'=>'MORELIKE','subKeyname'=>'MAX_LISTINGS'),'', True);
			$maxlisting=$System_Settings['KeyValue'];

			$sql="SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id." ORDER BY `Score`DESC";
			$sql_query=$this->db->query($sql);
			//echo '<pre>'; print_r($sql_query->result_array());
			$total_listing=count($sql_query->result_array());

			// Compare total listing (retrieve by morelike_listing) by maxlisting (maximum listing Limit)
			if($total_listing > $maxlisting)
			{
				$sql="SELECT * FROM (SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id." ORDER BY `Proximity` DESC LIMIT ".$maxlisting.") as temp ORDER BY `Score` DESC";
				$sql_query=$this->db->query($sql);
			}
			$_data['property']= $sql_query->result_array();
			$this->load->view('more-like/morelikeresult',$_data);
		}
		else
		{
			echo "No Match Found";
		}
	}

	function best()
	{
		$this->checkAuth();
		$Bedrooms = $this->input->post('Bedrooms');
		$Bathrooms = $this->input->post('Bathrooms');
		$property_id = $this->input->post('property_id');

		//Delete all the listings have both jumpscore and value score 0
		$this->commonmodel->deleteRecords('MoreLike_Listings',array("Property_id"=>$property_id,"JumpScore"=>0,"ValueScore"=>0));

		//Get the heightest score from MoreLike_Listings table
		$sql="SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id." ORDER BY `Score`DESC LIMIT 1";
		$sql_query=$this->db->query($sql);
		$more_like_array = $sql_query->row_array();
		//echo '<pre>'; print_r($more_like_array); 

		// Calculate the 25 and 50 percent of the heighest Score
		$heighestscore = $more_like_array['Score'];
		$percent25 = round(($heighestscore*25)/100);
		$percent50 = round(($heighestscore*50)/100);
		
		//Delete all the listings have Score less then 25% of the heighest score
		$sql="DELETE FROM MoreLike_Listings WHERE Property_id = ". $property_id ." AND Score < ". $percent25; 
		$this->db->query($sql);
		
		$sql="SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id. " ORDER BY `Score` DESC ";
		$sql_query=$this->db->query($sql);
		$more_like_results = $sql_query->result_array();
		//echo '<pre>'; print_r($more_like_results); 
		$delete_count=0;
		//echo '<pre>'; print_r($more_like_results); 
		//echo count($more_like_results); 

		if(count($more_like_results) > 10)
		{
			//echo $more_like_results[9]['Score'] ; exit;

			if($more_like_results[9]['Score'] < $percent50)
			{
				//keep only 10 records and delete all other records.
				foreach($more_like_results as $more_like_record)
				{
					if($delete_count>9)
					{
						$this->commonmodel->deleteRecords('MoreLike_Listings',array("Property_id"=>$property_id,"qProp_id"=>$more_like_record['qProp_id']));
					}
					$delete_count++;
				}
			}
			else
			{
				//Keep 20 records and delete all other records.
				foreach($more_like_results as $more_like_record)
				{
					if($delete_count>19)
					{
						$this->commonmodel->deleteRecords('MoreLike_Listings',array("Property_id"=>$property_id,"qProp_id"=>$more_like_record['qProp_id']));
					}
					$delete_count++;;
				}
			}
		}
		$sql="SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id." ORDER BY `Score` DESC";
		$sql_query=$this->db->query($sql);
		$data['property'] = $sql_query->result_array();
		$this->load->view('more-like/morelikeresult',$data);
	}


	function distance($lat1, $lon1, $lat2, $lon2, $unit) 
	{
		  $theta = $lon1 - $lon2;
		  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		  $dist = acos($dist);
		  $dist = rad2deg($dist);
		  $miles = $dist * 60 * 1.1515;
		  $unit = strtoupper($unit);

		 if ($unit == "K") 
		{
			return ($miles * 1.609344);
		}
		else if ($unit == "M") // for meter
		{
			return ($miles * 1609.344);
		} 
		else
		{
			return $miles;
		}
	}

	function Polygon_match()
	{
		$this->checkAuth();
		$property_id				= $this->input->post('property_id');
		$Location_id					= $this->input->post('Location_id');
		$PropertyType			= $this->input->post('PropertyType');

		$Polygon_id = $this->commonmodel->getRecords('MoreLike_Listings','Polygon_id',array('Property_id'=>$property_id,'qProp_id'=>$property_id),'', True); 
		//echo $Polygon_id['Polygon_id'];exit;
		//echo '<pre>'	;print_r($Polygon_id);
		if (count($Polygon_id) > 0)
		{
			$Base_Polygon_Stats_data = $this->commonmodel->getRecords('Polygon_Stats','AveragePrice,MedianPrice, AveragePriceSqft, MedianPriceSqft',array('Polygon_id'=>$Polygon_id['Polygon_id'],'PropertyType'=>$PropertyType),'', True);
			//echo '<pre>'	;print_r($Base_Polygon_Stats_data);//exit;
			
			if (count($Base_Polygon_Stats_data) > 0)
			{
				$Base_AveragePrice						=$Base_Polygon_Stats_data['AveragePrice'];
				$Base_AveragePriceSqft				=$Base_Polygon_Stats_data['AveragePriceSqft'];
				$Base_MedianPrice						=$Base_Polygon_Stats_data['MedianPrice'];
				$Base_MedianPriceSqft				=$Base_Polygon_Stats_data['MedianPriceSqft'];
				
				$min_Base_AveragePrice				=round($Base_AveragePrice - ($Base_AveragePrice*0.20));
				$max_Base_AveragePrice			=round($Base_AveragePrice + ($Base_AveragePrice*0.20));
				$min_Base_MedianPrice				=round($Base_MedianPrice - ($Base_MedianPrice*0.15));
				$max_Base_MedianPrice				=round($Base_MedianPrice + ($Base_MedianPrice*0.15));
				
				$min_Base_AveragePriceSqft		=round($Base_AveragePriceSqft - ($Base_AveragePriceSqft*0.15));
				$max_Base_AveragePriceSqft		=round($Base_AveragePriceSqft + ($Base_AveragePriceSqft*0.15));
				$min_Base_MedianPriceSqft		=round($Base_MedianPriceSqft - ($Base_MedianPriceSqft*0.10));
				$max_Base_MedianPriceSqft		=round($Base_MedianPriceSqft + ($Base_MedianPriceSqft*0.10));
				
				$total_score=0;
				$PolyAvgPrc=0;
				$PolyMedPrc=0;
				$PolyAvgPerft=0;
				$PolyMedPerft=0;

				$MoreLike_Listings_array = $this->commonmodel->getRecords('MoreLike_Listings','Polygon_id,qProp_id',array('Property_id'=>$property_id),'', False);
				//echo '<pre>'	;print_r($MoreLike_Listings_array);exit;
				foreach($MoreLike_Listings_array as $MoreLike_Listings_row)
				{
					$Polygon_Stats_data=array();

					$Polygon_Stats_data = $this->commonmodel->getRecords('Polygon_Stats','AveragePrice,MedianPrice, AveragePriceSqft, MedianPriceSqft',array('Polygon_id'=>$MoreLike_Listings_row['Polygon_id'], 'PropertyType'=>$PropertyType),'', True);
					//echo '<pre>'	;print_r($Polygon_Stats_data);//continue;
				
					$MoreLike_Listings_score = $this->commonmodel->getRecords('MoreLike_Listings',' qProp_id, Score', array('Property_id'=>$property_id, 'qProp_id'=>$MoreLike_Listings_row["qProp_id"]), '' , True);
					//echo '<pre>'	;print_r($MoreLike_Listings_score);exit;

					//old total score
					$total_score=$MoreLike_Listings_score['Score'];
					
					// Start Comparison
					if (count($Polygon_Stats_data) > 0)
					{
						$AveragePrice				=	$Polygon_Stats_data['AveragePrice'];
						$AveragePriceSqft		=	$Polygon_Stats_data['AveragePriceSqft'];
						$MedianPrice				=	$Polygon_Stats_data['MedianPrice'];
						$MedianPriceSqft		=	$Polygon_Stats_data['MedianPriceSqft'];
				
						//Compare base AveragePrice
						if($AveragePrice >= $min_Base_AveragePrice && $AveragePrice <= $max_Base_AveragePrice)
						{
							//SET 
							$PolyAvgPrc=0;
							$result['PolyAvgPrc'] = 2;
						}
						elseif($AveragePrice < $Base_AveragePrice)
						{
							$PolyAvgPrc=-1;
							$result['PolyAvgPrc'] = 0;
						}
						elseif($AveragePrice > $Base_AveragePrice)
						{
							$PolyAvgPrc=1;
							$result['PolyAvgPrc'] = 1;
						}
						
						//Compare base MedianPrice
						if($MedianPrice>=$min_Base_MedianPrice && $MedianPrice<=$max_Base_MedianPrice)
						{	
							$PolyMedPrc=0;
							$result['PolyMedPrc'] = 2;
						}
						elseif($MedianPrice < $Base_MedianPrice)
						{	
							$PolyMedPrc=-1;
							$result['PolyMedPrc'] = 0;
						}
						elseif($MedianPrice > $Base_MedianPrice )
						{	
							$PolyMedPrc=1;
							$result['PolyMedPrc'] = 1;
						}

						//Compare base AveragePriceSqft
						if($AveragePriceSqft>=$min_Base_AveragePriceSqft && $AveragePriceSqft<=$max_Base_AveragePriceSqft)
						{
							$PolyAvgPerft=0;
							$result['PolyAvgPerft'] = 2;
						}
						elseif($AveragePriceSqft < $Base_AveragePriceSqft)
						{
							$PolyAvgPerft=-1;
							$result['PolyAvgPerft'] = 1;
						}
						elseif($AveragePriceSqft > $Base_AveragePriceSqft)
						{
							$PolyAvgPerft=1;
							$result['PolyAvgPerft'] = -1;
						}
						
						//Compare base MedianPriceSqft
						if($MedianPriceSqft >=$min_Base_MedianPriceSqft && $MedianPriceSqft<=$max_Base_MedianPriceSqft)
						{	
							$PolyMedPerft=0;
							$result['PolyMedPerft'] = 2;
						}
						elseif($MedianPriceSqft < $Base_MedianPriceSqft)
						{	
							$PolyMedPerft=-1;
							$result['PolyMedPerft'] = 1;
						}
						elseif($MedianPriceSqft > $Base_MedianPriceSqft)
						{	
							$PolyMedPerft=1;
							$result['PolyMedPerft'] = -1;
						}
						//echo $MoreLike_Listings_row['qProp_id'];
						//echo "<pre>";print_r($result); 
						//$score=$total_score +
						$score= $result['PolyAvgPrc'] + $result['PolyMedPrc'] + $result['PolyAvgPerft'] + $result['PolyMedPerft'];
						$_score= $total_score+$score;
						$data=array('PolyAvgPrc'=>$PolyAvgPrc,'PolyMedPrc'=>$PolyMedPrc,'PolyAvgPerft'=>$PolyAvgPerft,'PolyMedPerft'=>$PolyMedPerft,'Score'=>$_score);
						//$data=array('PolyAvgPrc'=>$PolyAvgPrc,'PolyMedPrc'=>$PolyMedPrc,'PolyAvgPerft'=>$PolyAvgPerft,'PolyMedPerft'=>$PolyMedPerft);
						//echo "<pre>";print_r($data); 
						//echo "<pre>";print_r($data); 
						//update scores
						$this->listingmodel->commonInsertUpdate('MoreLike_Listings',$data,"Property_id =  ".$property_id." AND qProp_id=".$MoreLike_Listings_row['qProp_id']);
					}
				}
			}
		}

		// calaculate maximum listing Limit
		$System_Settings = $this->commonmodel->getRecords('SystemSettings','KeyValue',array('Keyname'=>'MORELIKE','subKeyname'=>'MAX_LISTINGS'),'', True);
		$maxlisting=$System_Settings['KeyValue'];


		$sql="SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id." ORDER BY `Score`DESC";
		$sql_query=$this->db->query($sql);
		//echo '<pre>'; print_r($sql_query->result_array());
		$total_listing=count($sql_query->result_array());

		// Compare total listing (retrieve by morelike_listing) by maxlisting (maximum listing Limit)
		if($total_listing > $maxlisting)
		{
			$sql="SELECT * FROM (SELECT * FROM MoreLike_Listings WHERE Property_id =  ".$property_id." ORDER BY `Proximity` DESC LIMIT ".$maxlisting.") as temp ORDER BY `Score` DESC";
			$sql_query=$this->db->query($sql);
		}
		$_data['property']= $sql_query->result_array();
		$this->load->view('more-like/morelikeresult',$_data);
	}

/************************************** Token & QWord Data ****************************************/
	function showMoreLikeQwords()
	{
		$this->checkAuth();
		$data['Like_id'] = $like_id = $this->input->post('like_id');
		$property_id = $this->input->post('property_id');
		$sql="SELECT r.QDNA, r.Token, r.QWords FROM Real_QWords r,MoreLike_QWords m WHERE r.Property_id = ".$property_id."  and r.Token=m.Token and  r.QWords = m.QWords and m.Like_id=".$like_id." and r.QDNA=m.QDNA";
		$query=$this->db->query($sql);
		$data['property_array'] = $query->result_array();
		$this->load->view('more-like/show-more-like-qwords',$data);
	}


/************************************** Fetching RETS Data ****************************************/

function rets_data()
{ 
		$this->checkAuth();
		$this->load->view('includes/header');
		$this->load->view('RETS/retsdata');
		$this->load->view('includes/footer');
}

function rets_data_list()
{
	$this->checkAuth();
	require('retsconfig-production.php');
	require('phrets.php');
	// Establish RETS connection
	$rets = new phRETS;
	if (defined(RETS_SERVER_VERSION)) $rets->AddHeader('RETS-Version', 'RETS/'.RETS_SERVER_VERSION);
	$rets->AddHeader("User-Agent", RETS_USERAGENT);
	$retsConnection = $rets->Connect(RETS_LOGIN_URL, RETS_USERNAME, RETS_PASSWORD);

	$selection		= $this->input->post('selection');
	$searchby		= $this->input->post('searchby');
	if($searchby	== 1)
	{
		$retsQuery="(MLSNumber=".$selection.")";
	}
	else
	{
		$retsQuery="(Matrix_Unique_ID=".$selection.")";
	}
	$className="RESI";
	$search = $rets->SearchQuery("Property", $className, $retsQuery);

	/* If search returned results */
	if($rets->TotalRecordsFound() > 0) 
	{
		while($data = $rets->FetchRow($search)) 
		{
			$records['property'][]=$data;
		}
		$this->load->view('RETS/retsdataresult',$records);
	}
}





}
//end controller
/* End of file listing.php */
/* Location: ./application/controllers/listing.php */