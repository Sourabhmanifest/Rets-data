function cancelTo(page)
{
	window.location = site_url+page;
}

function searchProperty()
{ 
	err=0;
	$('#main-tabs').children().removeClass('selected');
	$('#text-nav').addClass('selected');
	
	$('#e_selection').html('<img src="images/loader.gif" align="absmiddle">');

	selection = $.trim($('#search').val());
	searchby =  $('input:radio[name="searchby"]:checked').val();

	if(selection.length <= 0)
	{ 
		$('#e_selection').html('Please enter selection id.');
		err=1;
	}

	if(err == 1 )
	{
		$('#property-list').hide();
		$('#property-detail').hide();
		return false;
	}

	data = 'selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/propertylist",
		data: data,
		success: function (data){
			if (data)
			{ 
				$("#property-list").html(data);
				$('#property-list').show();
				$('#e_selection').html('');
				listText();
			}
		}
	});

	$('#property-detail').show();
}

function listText()
{ 
	selection = $('#search').val();
	searchby =  $('input:radio[name="searchby"]:checked').val();

	data = 'selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/listtext",
		data: data,
		success: function (data){
			if (data)
			{
				$("#detail-info").html(data);
			}
		}
	});
}

function realText(Property_id,textsource)
{ 
	data = '';
	$.ajax({
		type: "GET",
		url: site_url + "listing/realtext/"+Property_id+"/"+textsource,
		data: data,
		success: function (data){
			if (data)
			{ 
				$("#realtext").html(data);
			}
		}
	});
}

function listLocation()
{ 
	selection = $('#search').val();
	searchby =  $('input:radio[name="searchby"]:checked').val();
	data = 'selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/listlocation",
		data: data,
		success: function (data){
			if (data)
			{ 
				$("#detail-info").html(data);
			}
		}
	});
}

function listStreet(keyword, search_by)
{ 
	data = '';
	ft = search_by == 'Latitude' ? $("#lat-long-ft").val() : "";
				
	$.ajax({
		type: "GET",
		url: site_url + "listing/listStreet/"+keyword+"/"+search_by+"/"+ft,
		data: data,
		success: function (data){
			if (data)
			{ 
				$("#street-info").html(data);
			}
		}
	});
}

function listOffice()
{ 
	selection = $('#search').val();
	searchby = $('#search').val();
	data = 'selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/listoffice",
		data: data,
		success: function (data){
			if (data)
			{ 
				$("#detail-info").html(data);
			}
		}
	});
}

function findOfficeOrAgent()
{
	search_keyword = $('#search-keyword').val();
	search_type = $('input:radio[name="search_type"]:checked').val();
	search_by = $('input:radio[name="search_by"]:checked').val(); 
	
	data = 'search_keyword='+search_keyword+'&search_type='+search_type+'&search_by='+search_by;
	$.ajax({
		type: "POST",
		url: site_url + "listing/findofficeoragent",
		data: data,
		success: function (data){
			if (data)
			{ 
				$("#search-info").html(data);
			}
		}
	});
}

function selectNav(tabid)
{ 
	$("#"+tabid).siblings().removeClass('selected');
	$("#"+tabid).addClass('selected');
}


function statusProcess()
{  
	process =  $('input:radio[name="statusbyid"]:checked').val();
	$("#"+process).html('<img src="images/loader.gif" align="absmiddle">');

	data='process='+process;
	$.ajax({
		type: "POST",
		url: site_url + "listing/statusprocess",
		data: data,
		success: function (data){
			if (data)
			{ 
				$("#process-log-status").html(data);
				$("#"+process).html('');
			}
		}
	});
}

function findPhrase()
{
	err=0;
	$('#e_search_keyword').html('<img src="images/loader.gif" align="absmiddle">');

	var keyword = $.trim($('#search_keyword').val());
	var qdna = $('input:radio[name="qdna"]:checked').val();
	var find = $('input:radio[name="find"]:checked').val();

	if(keyword.length <= 0)
	{ 
		$('#e_search_keyword').html('Please enter keyword.');
		err=1;
		$('#key-result').hide();
		return false;
	}

	data ='keyword='+keyword+'&qdna='+qdna+'&find='+find;
	$.ajax({
		type: "POST",
		url: site_url + "listing/keyresult",
		data: data,
		success: function (data){
			if (data)
			{ 
				$("#key-result").html(data);
				$("#key-result").show();
				$('#e_search_keyword').html('');
			}
			else{
				$('#e_search_keyword').html('No data found');
				err=1;
			}
		}
	});
}

function addEditKeyword(keyword_id)
{
	data ='keyword_id='+keyword_id;
	$.ajax({
		type: "POST",
		async: false,
		url: site_url + "listing/addEditKeyword/",
		data: data,
		success: function(data)			
		{
			$("#popupEdit").html(data);		
		}
	});
}

function saveKeyword(keyword_id)
{ 	
	err=0	;
	$('#e_keyword').html('');
	$('#e_seq').html('');
	$('#e_rel').html('');
	$('#e_token').html('');
	$('#e_wt').html('');
	$('#e_adj').html('');	
	

	strRegExp = "[^A-Za-z0-9\\s\,]";
	numRegExp = "[^0-9\-]";
	
	var qdna = $.trim($('#qdna').val());
	if(qdna == '--Select--')
	{ 		
		$('#e_qdna').html('Please select QDNA');
		err=1;	
	}

	var keyword = $.trim($('#keyword').val());
	if(keyword.length <= 0)
	{ 		
		$('#e_keyword').html('Please enter keyword');
		err=1;	
	}
	else
	{
		charpos = keyword.search(strRegExp);
		if (charpos >=0)
		{
			$('#e_keyword').html('Keyword should be alpha-numeric only.');
			err=1;
		}
	}

	var adj = $.trim($("#adjective").val());
	charpos = adj.search(strRegExp);
	if(charpos >=0)
	{ 				
		$('#e_adj').html('Adjective should be alpha-numeric only.');
		err=1;
	}

	var jumpkey =$('input:radio[name="jumpkey"]:checked').val();
	var seq = $.trim($("#seq").val());
	charpos = seq.search(numRegExp);
	if(charpos >=0)
	{ 		
		$('#e_seq').html('Seq should be numeric only.');
		err=1;	
	}

	var relative = $.trim($("#relative").val());
	charpos = relative.search(numRegExp);
	if(charpos >=0)
	{ 		
		$('#e_rel').html('Relative should be numeric only.');
		err=1;	
	}
	
	var wt = $.trim($("#wt").val());
	charpos = wt.search(numRegExp);
	if(charpos >=0)
	{ 		
		$('#e_wt').html('Weight should be numeric only.');
		err=1;	
	}

	var token = $.trim($("#token").val());
	if(token.length <= 3)
	{ 		
		$('#e_token').html('Please enter four digit token value');
		err=1;	
	}
	else
	{
		charpos = token.search(strRegExp);
		if (charpos >=0)
		{
			$('#e_token').html('Token should be alpha-numeric only.');
			err=1;
		}
	}		
	
	if(err == 1)
		return false;

	data = 'qdna='+qdna+'&seq='+seq+'&keyword='+keyword+'&relative='+relative+'&adjective='+adj+'&wt='+wt+'&jumpkey='+jumpkey+'&token='+token;
	
	$.ajax({
		type: "POST",
		url: site_url + "listing/saveKeyword/"+keyword_id,
		data: data,
		success: function (data){
			$("#qdna_"+keyword_id).html(qdna);
			$("#seq_"+keyword_id).html(seq);
			$("#keyword_val_"+keyword_id).html(keyword);
			$("#relative_"+keyword_id).html(relative);
			$("#adjective_"+keyword_id).html(adj);
			$("#wt_"+keyword_id).html(wt);
			$("#jumpkey_"+keyword_id).html(jumpkey);
			$("#token_"+keyword_id).html(token);
			$("#success_msg").show();
			$("#success_msg").html('Keyword '+data+' successfully.');
			$("#success_msg").fadeOut(3000);
			closePopDiv('popupEdit');
		}
	});
	
	return false;
}

function deleteKeyword(KeyWord_id)
{
	var confirmation = confirm("Are you sure you want to delete this keyword.")
	if(confirmation)
	{ 
		data = 'KeyWord_id='+KeyWord_id;
		$.ajax({
			type: "POST",
			url: site_url + "listing/deleteKeyword",
			data: data,
			success: function (data){
				if(data == '1')
				{
					$("#keyword_"+KeyWord_id).remove();
				}else{
					alert('Some error occured during delete.');
				}
			}
		});
	}
	return false;
}

function addEditUser(User_id)
{
	data ='user_id='+User_id;
	$.ajax({
		type: "POST",
		async: false,
		url: site_url + "listing/addEditUser/",
		data: data,
		success: function(data)			
		{
			$("#popupEdit").html(data);		
		}
	});
}

err1=0	;
function checkUserValidation(User_id)
{ 
	$('#e_uname').html('');
	strRegExp = "[^A-Za-z0-9\]";
	var uname = $.trim($('#uname').val());
	if(uname.length >= 4)
	{
		charpos = uname.search(strRegExp);
		if (charpos >=0)
		{
			$('#e_uname').html('Username should be alpha-numeric only.');
			err1=1;
		}
		else
		{
			$("#e_uname").html('');
			$("#e_uname").html('<img src="images/loader.gif" align="absmiddle">&nbsp;Checking availability...');
			data = 'uname='+uname;
			$.ajax({			
				type: "POST",
				url: site_url + "listing/checkUser/"+User_id,
				data: data,
				success: function(data)			
				{ 
					if(data == 'user'){
						$("#e_uname").html('Username already exist');		
						err1=1;
					}
					else{
						$("#e_uname").html('');
						err1=0;
					}
				}
			});
		}
	}
	else
	{
		$("#e_uname").html('<font color="red">The username should have at least <strong>4</strong> characters.</font>');
		err1=1;
	}
}

/*function checkEmailValidation(User_id)
{
	$('#e_email').html('');
	var email = $.trim($('#email').val());
	var atpos=email.indexOf("@");
	var dotpos=email.lastIndexOf(".");
	
	if(email.length <=0)
	{ 		
		$('#e_email').html('Please enter email.');
		err2=1;
	}
	else if (atpos<1 || dotpos<atpos+2 || dotpos+2>=email.length)
	{
		$('#e_email').html("Please enter a valid email.");
		err2=1;
	}
	else
	{
		$("#e_email").html('<img src="images/loader.gif" align="absmiddle">&nbsp;Checking availability...');
		data = 'email='+email;
		$.ajax({
				type: "POST",				
				url: site_url + "listing/checkUser/"+User_id,
				data: data,
				success: function(data)			
				{
					if(data == 'email'){
						$("#e_email").html('Email already exist');
						err2=1;		
					}
					else{
						$("#e_email").html('');
						err2=0;
					}
				}
		});
	}
}*/


function checkEmailValidation(User_id)
{
	$('#e_email').html('');
	var email = $.trim($('#email').val());
	var pattern = new RegExp(/^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/);
	
	if(email.length <=0)
	{ 		
		$('#e_email').html('Please enter email.');
		err2=1;
	}
	else if (!pattern.test(email))
	{
		$('#e_email').html("Please enter a valid email.");
		err2=1;
	}
	else
	{
		$("#e_email").html('<img src="images/loader.gif" align="absmiddle">&nbsp;Checking availability...');
		data = 'email='+email;
		$.ajax({
				type: "POST",				
				url: site_url + "listing/checkUser/"+User_id,
				data: data,
				success: function(data)			
				{
					if(data == 'email'){
						$("#e_email").html('Email already exist');
						err2=1;		
					}
					else{
						$("#e_email").html('');
						err2=0;
					}
				}
		});
	}
}


function saveUser(User_id)
{ 
	if(!err1)
	{
		$('#e_uname').html('');
		$('#e_fullname').html('');
		$('#e_email').html('');
		$('#e_newpwd').html('');
	}
	
	err = 0;
	strRegExp = "[^A-Za-z0-9\]";
	charRegExp ="[^A-Za-z \\s]";
	
	if(User_id =='')
	{
		var email = $.trim($('#email').val());
		var pattern = new RegExp(/^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/);

		if(email.length <=0)
		{ 		
			$('#e_email').html('Please enter email.');
			err=1;	
		}
		else if (!pattern.test(email))
		{
			$('#e_email').html("Please enter a valid email.");
			err=1;
		}
	}
	
	var uname = $.trim($('#uname').val());
	if(uname.length <= 0)
	{ 		
		$('#e_uname').html('Please enter Username');
		err=1;	
	}
	else
	{
		charpos = uname.search(strRegExp);
		if (charpos >=0)
		{
			$('#e_uname').html('Username should be alpha-numeric only.');
			err=1;
		}
	}

	var fullname = $.trim($('#fullname').val());
	if(fullname.length <= 0)
	{ 		
		$('#e_fullname').html('Please enter your full name');
		err=1;	
	}
	else
	{
		charpos = fullname.search(charRegExp);
		if (charpos >=0)
		{
			$('#e_fullname').html('Must be character only.');
			err=1;
		}
	}

	var newpwd = $.trim($('#newpwd').val());
	if(User_id != '')
	{
		charpos = newpwd.search(strRegExp);
		if (charpos >=0)
		{
			$('#e_newpwd').html('Password should be alpha-numeric only.');
			err=1;
		}
	}
	else if(newpwd.length <= 0)
		{ 		
			$('#e_newpwd').html('Please enter Password');
			err=1;
		}
		else
		{
			charpos = newpwd.search(strRegExp);
			if (charpos >=0)
			{
				$('#e_newpwd').html('Password should be alpha-numeric only.');
				err=1;
			}
		}
	
	if(err == 1 || err1 == 1 || err2 == 1)
		return false;
}

function deleteUser(User_id)
{
	var confirmation = confirm("Are you sure you want to delete this user.")
	if(confirmation)
	{ 
		data = 'User_id='+User_id;
		$.ajax({
			type: "POST",
			url: site_url + "listing/deleteUser",
			data: data,
			success: function (data) {
				if(data == '1')
				{
					$("#user_"+User_id).remove();
				}
				else {
					alert('Some error occured during delete.');
				}
			}
		});
		//window.location =site_url+'listing/deleteRecord/'+table+"/"+id+"/"+redirect;
	}
	return false;
}

//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//End of Realmark JS
//---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

// this function disables when radio is not checked
function toggleEnableById(chk, enable_id, disable_id)
{ 
	if(chk.checked)
	{
		document.getElementById(enable_id).disabled = false;
		document.getElementById(disable_id).disabled = true;
		document.getElementById(enable_id).className = "input-shadow";
		if (disable_id == 'company_name')
		{
			document.getElementById(disable_id).className = "";
			document.getElementById(disable_id).style.width = "200px";
		}
	}
}

function changeStatus(table,status,id)
{ 
	data = 'table='+table+'&status='+status+'&id='+id;
	$.ajax({
		type: "POST",
		url: site_url + "admin/changestatus",
		data: data,
		success: function (data){
			if (data == 1)
			{ 
				$("#status"+id).html('<span style="cursor:pointer;" onClick="changeStatus(\''+table+'\',0,\''+id+'\')"><img src="images/right.png" /></span>');
			}
			else
			{
				$("#status"+id).html('<span style="cursor:pointer;" onClick="changeStatus(\''+table+'\',1,\''+id+'\')"><img src="images/minus.png" /></span>');
			}
		}
	});
}


function changeStatusNew(table,status,id)
{ 
	data = 'table='+table+'&status='+status+'&id='+id;
	$.ajax({
		type: "POST",
		url: site_url + "admin/changestatus",
		data: data,
		success: function (data){
			if (data == 1)
			{ 
				$("#status"+id).html('<span style="cursor:pointer;" onClick="changeStatusNew(\''+table+'\',0,\''+id+'\')"><img src="images/right.png" /></span>');
			}
			else
			{
				$("#status"+id).html('<span style="cursor:pointer;" onClick="changeStatusNew(\''+table+'\',1,\''+id+'\')"><img src="images/minus.png" /></span>');
			}

			if(status==1)
			{
				$("#FOD"+id).show();
			}else{
				$("#FOD"+id).hide();
			}
		}
	});
}

function tooltip(table,id)
{ 
		$.ajax({
			type: "GET",
			url: site_url + "admin/callback_statustooltip/"+table+"/"+id,
			success: function (data){ //alert(data);
				$("#tip"+id).html(data);
			}
		});	
}

var current_fod_id;
function changeFOD(student_id)
{ 
	data = 'current_fod_id='+current_fod_id+'&student_id='+student_id;
	$.ajax({
		type: "POST",
		url: site_url + "admin/changeFOD",
		data: data,
		success: function (data){

			$("#FOD"+student_id).html('<span style="cursor:pointer;" onClick="javascript:void(0);"><img src="images/right.png" /></span>');
			$("#FOD"+current_fod_id).html('<span style="cursor:pointer;" onClick="changeFOD(\''+current_fod_id+'\')"><img src="images/minus.png" /></span>');
			current_fod_id = student_id;
			window.location.reload();
		}
	});
}

function addEditCategory(category_id)
{ 
	$.ajax({
		type: "POST",
		async: false,
		url: site_url + "admin/addeditcategory/"+category_id,
		data: '',
		success: function (data) { 
			$("#popupEdit").html(data);
		}
	});
}

function validateDepartment()
{
	err = 0;
	$('#e_category_name').html('');
	
	strRegExp = "[^A-Za-z0-9\\s]";

	category_name = $.trim($('#category_name').val());
	charpos = category_name.search(strRegExp);
	if(category_name.length <= 0)
	{ 
		$('#e_category_name').html('Please enter category name.');
		err=1;
	}

	if(err == 1)
		return false;
}

function addEditSubcategory(subcategory_id)
{ 
	$.ajax({
		type: "POST",
		async: false,
		url: site_url + "admin/addeditsubcategory/"+subcategory_id,
		data: '',
		success: function (data){ 
			$("#popupEdit").html(data);
		}
	});
}

function validateSubcategory()
{
	err = 0;
	$('#e_subcategory_name').html('');
	$('#e_subcategory_desc').html('');
	$('#e_category_name').html('');
	
	strRegExp = "[^A-Za-z0-9\\s]";

	subcategory_name = $.trim($('#subcategory_name').val());
	charpos = subcategory_name.search(strRegExp);
	if(subcategory_name.length <= 0)
	{ 
		$('#e_subcategory_name').html('Please enter sub-category name.');
		err=1;
	}

	subcategory_desc = $.trim($('#subcategory_desc').val());
	if(subcategory_desc.length <= 0)
	{ 
		$('#e_subcategory_desc').html('Please enter sub-category description.');
		err=1;
	}

	category_id = $.trim($('#category_id').val());
	if(category_id == "--Select--")
	{  
		$('#e_category_name').html('Please select category.');
		err=1;
	}

	if(err == 1)
		return false;
}

function getSubcategory(category_id,flag)
{ 
	$.ajax({
		type: "GET",
		async: false,
		url: site_url + "admin/getsubcategory/"+category_id,
		data: '',
		success: function (data){ 
			$("#subcategory-div").html(data);
		}
	});
}

function validatePlace()
{
	err = 0;
	$('#e_place_name').html('');
	$('#e_category').html('');
	$('#e_subcategory').html('');
	
	strRegExp = "[^A-Za-z0-9\\s]";

	place_name = $.trim($('#place_name').val());
	charpos = place_name.search(strRegExp);
	if(place_name.length <= 0)
	{ 
		$('#e_place_name').html('Please enter place name.');
		err=1;
	}

	category = $('#category').val();
	if (category == '--Select--')
	{
		$('#e_category').html('Please select category.');
		err=1;
	}

	subcategory = $('#subcategory').val();
	if (subcategory == '--Select--')
	{
		$('#e_subcategory').html('Please select subcategory.');
		err=1;
	}
	if(err == 1)
		return false;
}

function filterSubcategory(category_id)
{ 
	window.location = base_url+'admin/subcategory/'+category_id;
}

function filterPlace(category_id, subcategory_id)
{ 
	window.location = base_url+'admin/place/'+category_id+"/"+subcategory_id;
}

function adddEditAddStatus(table,status,id)
{ 
	data = 'table='+table+'&status='+status+'&id='+id;
	$.ajax({
		type: "POST",
		url: site_url + "admin/adddeditaddstatus",
		data: data,
		success: function (data){
			if (data == 1)
			{ 
				$("#"+table+id).html('<span style="cursor:pointer;" onClick="adddEditAddStatus(\''+table+'\',0,\''+id+'\')"><img src="images/right.png" /></span>');
			}
			else
			{
				$("#"+table+id).html('<span style="cursor:pointer;" onClick="adddEditAddStatus(\''+table+'\',1,\''+id+'\')"><img src="images/minus.png" /></span>');
			}
		}
	});
}

function getSubcategories(val,getrecord)
{

	var url = site_url+'admin/getsubcategory/'+val+'/'+getrecord;
	$.post(url,function(data){
		$("#subcategory-div").html(data);
    });
}

function realPhoto()
{ 
	selection = $('#search').val();
	searchby =  $('input:radio[name="searchby"]:checked').val();

	data = 'selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/realPhoto",
		data: data,
		success: function (data){
			if (data)
			{
				$("#detail-info").html(data);
			}
		}
	});
}

function imagePrev(ListingID,count)
{ 
	selection = $('#search').val();
	Seqno = $('#seqno').val();

	searchby =  $('input:radio[name="searchby"]:checked').val();
	
	Seqno=--Seqno;
	if(Seqno==0)
	{
		Seqno=count;
	}
	data = 'Seqno='+Seqno+'&ListingID='+ListingID+'&selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/realPhotoNext",
		data: data,
		success: function (data){
			if (data)
			{
				$("#detail-info").html(data);
				$('#seqno').val(Seqno);

			}
		}
	});
}


function imageNext(ListingID,count)
{ 
	selection = $('#search').val();
	Seqno = $('#seqno').val();

	searchby =  $('input:radio[name="searchby"]:checked').val();
	Seqno=++Seqno;
	if(count<Seqno)
	{
		Seqno=1;
	}
	data = 'Seqno='+Seqno+'&ListingID='+ListingID+'&selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/realPhotoNext",
		data: data,
		success: function (data){
			if (data)
			{
				$("#detail-info").html(data);
				$('#seqno').val(Seqno);
			}
		}
	});
}

function imageJump(ListingID,count)
{ 
	selection = $('#search').val();
	Seqno = $('#seqno').val();

	searchby =  $('input:radio[name="searchby"]:checked').val();

	data = 'Seqno='+Seqno+'&ListingID='+ListingID+'&selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/realPhotoNext",
		data: data,
		success: function (data){
			if (data)
			{
				$("#detail-info").html(data);
				$('#seqno').val(Seqno);
			}
		}
	});
}

function tagline(Photo_id)
{
	selection = $('#search').val();
	searchby =  $('input:radio[name="searchby"]:checked').val();
	tag = $('#tag').val();
	if(tag=='')
	{
		alert('field should not be blank.');
		err =1;
		return false;
	}
	if(Photo_id==null||Photo_id=='')
	{
		alert('Photo id not found.');
		err =1;
		return false;
	}
	
	data = 'Photo_id='+Photo_id+'&tag='+tag+'&selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/tagline",
		data: data,
		success: function (data){
			if (data)
			{	
				$("#msg").show();
				$("#msg").html('Comment '+data+' successfully.');
				$("#msg").fadeOut(3000);
			}
		}
	});
}

function addEditAreaNames(Area_id,Neighborhood,Count)
{
	data ='Area_id='+Area_id+'&Neighborhood='+Neighborhood+'&Count='+Count;
	$.ajax({
		type: "POST",
		async: false,
		url: site_url + "listing/addEditAreaNames/",
		data: data,
		success: function(data)			
		{
			$("#popupEdit").html(data);		
		}
	});
}

function orderBy()
{
	$('#e_search_keyword').html('<img src="images/loader.gif" align="absmiddle">');
	x = $('#orderBy').val();
	data='like='+x;
	$.ajax({
		type: "POST",
		url: site_url + "listing/areaNamesDisplay",
		data: data,
		success: function (data){
			if (data)
			{
				$("#areaDisplay").html(data);
				$('#e_search_keyword').html('');
			}
		}
	});
}
/*     Delete User    */
function deleteAreaName(Area_id,Neighborhood)
{
	var confirmation = confirm("Are you sure you want to delete this AreaName.")
	if(confirmation)
	{ 
		data = 'Area_id='+Area_id+'&Neighborhood='+Neighborhood;
		$.ajax({
			type: "POST",
			url: site_url + "listing/deleteAreaName",
			data: data,
			success: function (data) {
				if(data == '1')
				{ 
					$("#"+Area_id).remove();
				}
				else
				{
					alert('Some error occured during delete.');
				}
			}
		});
		//window.location =site_url+'listing/deleteRecord/'+table+"/"+id+"/"+redirect;*/
	}
	return false;
}

function saveAreaNames() 
{
	$('#e_search_keyword').html('<img src="images/loader.gif" align="absmiddle">');
	var order_by = $("#orderBy").val();
	var AreaName = $("#AreaName").val();
	var Count = $("#Count").val();
	var Area_id = $("#Area_id").val();
	var save = $("#save").val();

	data = 'order_by='+order_by+'&AreaName='+AreaName+'&Count='+Count+'&Area_id='+Area_id+'&save='+save;
	$.ajax({
		type: "POST",
		url: site_url + "listing/saveAreaNames",
		data: data,
		success: function (data) {
			$('#'+Area_id+"_area_name").html(AreaName);
			$('#e_search_keyword').html('');
		}
	})
}

function more_like_list()
{ 
	err=0;
	$('#more_like_list').hide();
	$('#morelikelistNA').hide();
	$('#detail_result').hide();
	$('#detailresult').hide();
	$('#e_selection').html('<img src="images/loader.gif" align="absmiddle">');
	selection = $.trim($('#search').val());
	searchby =  $('input:radio[name="searchby"]:checked').val();

	if(selection.length <= 0)
	{ 
		$('#e_selection').html('Please enter selection id.');
		err=1;
	}

	if(err == 1 )
	{
		$('#more_like_list').hide();
		return false;
	}

	data = 'selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/more_like_list",
		data: data,
		success: function (data){
			if (data=="No data found")
			{
				$('#detail_result').hide();
				$('#detailresult').hide();
				$("#Not_available").html(data);
				$('#morelikelistNA').show();
				$('#e_selection').html('');
			}
			else
			{
				$("#more_like_list").html(data);
				$('#e_selection').html('');
				$('#more_like_list').show();
			}
		}
	});
}

function delete_more_listing(property_id)
{
	$('#detail_result').hide();
	$('#detailresult').hide();
	$('#nextstepsbuttons').hide();
	$("#step3").removeAttr('disabled');
	$("#step4").removeAttr('disabled');
	$('#loader').html('<img src="images/loader.gif" align="absmiddle">');
	var Location_id			= $('#Location_id').text();
	var MinPrice					= $('#MinPrice').text();
	var MaxPrice				= $('#MaxPrice').text();
	var PricePerFoot			= $('#PricePerFoot').text();
	var MinSqft					= $('#MinSqft').text();
	var MaxSqft					= $('#MaxSqft').text();
	var MinPricePerFt		= $('#MinPricePerFt').text();
	var MaxPricePerFt		= $('#MaxPricePerFt').text();
	var MinBed					= $('#MinBed').text();
	var MaxBed					= $('#MaxBed').text();
	var MinBath					= $('#MinBath').text();
	var MaxBath				= $('#MaxBath').text();
	var Bedrooms				= $('#Bedrooms').text();
	var Bathrooms			= $('#Bathrooms').text();
	var Geodna					= $('#Geodna').text();
	var GarageSpaces		= $('#GarageSpaces').text();
	var PropertyType		= $('#PropertyType').text();

	data = 'property_id='+property_id+'&Location_id='+Location_id+'&MinPrice='+MinPrice+'&MaxPrice='+MaxPrice+'&PricePerFoot='+PricePerFoot+'&MinSqft='+MinSqft+'&MaxSqft='+MaxSqft+'&MinPricePerFt='+MinPricePerFt+'&MaxPricePerFt='+MaxPricePerFt+'&MinBed='+MinBed+'&MaxBed='+MaxBed+'&MinBath='+MinBath+'&MaxBath='+MaxBath+'&Bedrooms='+Bedrooms+'&Bathrooms='+Bathrooms+'&Geodna='+Geodna+'&GarageSpaces='+GarageSpaces+'&PropertyType='+PropertyType;
	$.ajax({
		type: "POST",
		url: site_url + "listing/delete_morelisting",
		data: data,
		success: function (data) {
			if (data=="No Match Found")
			{
				$("#Nodata").html(data);
				$('#detailresult').show();
				$('#loader').html('');
			}
			else
			{
				$("#detail_result").html(data);
				$('#detail_result').show();
				$('#nextstepsbuttons').show();
				$('#loader').html('');
			}
		}
	});
}

function Garagespace(property_id)
{
	var Bedrooms = $('#Bedrooms').text();
	var Bathrooms = $('#Bathrooms').text();
	var GarageSpaces = $('#GarageSpaces').text();
	$('#_loader').html('<img src="images/loader.gif" align="absmiddle">');
	data = 'property_id='+property_id+'&Bedrooms='+Bedrooms+'&Bathrooms='+Bathrooms+'&GarageSpaces='+GarageSpaces;
	$.ajax({
		type: "POST",
		url: site_url + "listing/Garage_space",
		data: data,
		success: function (data) {
			if (data=="No Match Found")
			{
				$("#Nodata").html(data);
				$('#detailresult').show();
				$('#loader').html('');
			}
			else
			{
				$("#detail_result").html(data);
				$('#detail_result').show();
				$('#nextstepsbuttons').show();
				$('#loader').html('');
				$("#step3").attr('disabled','disabled');
			}
		}
	});
}

function best(property_id)
{
	var Bedrooms = $('#Bedrooms').text();
	var Bathrooms = $('#Bathrooms').text();
	$('#_loader').html('<img src="images/loader.gif" align="absmiddle">');
	data = 'property_id='+property_id+'&Bedrooms='+Bedrooms+'&Bathrooms='+Bathrooms;
	$.ajax({
		type: "POST",
		url: site_url + "listing/best",
		data: data,
		success: function (data) {
			if (data=="No Match Found")
			{
				$("#Nodata").html(data);
				$('#detailresult').show();
				$('#_loader').html('');
			}
			else
			{
				$("#detail_result").html(data);
				$('#detail_result').show();
				$('#nextstepsbuttons').show();
				$('#_loader').html('');
				$("#best").attr('disabled','disabled');
			}
		}
	});
}


function Polygonmatch(property_id)
{
	var Location_id = $('#Location_id').text();
	var BasePerFt = $('#BasePricePerFoot').text();
	var PropertyType=$('#PropertyType').text();
	$('#_loader').html('<img src="images/loader.gif" align="absmiddle">');
	data = 'property_id='+property_id+'&Location_id='+Location_id+'&BasePerFt='+BasePerFt+'&PropertyType='+PropertyType;
	$.ajax({
		type: "POST",
		url: site_url + "listing/Polygon_match",
		data: data,
		success: function (data) {
			if (data=="No Match Found")
			{
				$("#Nodata").html(data);
				$('#detailresult').show();
				$('#loader').html('');
			}
			else
			{
				$("#detail_result").html(data);
				$('#detail_result').show();
				$('#nextstepsbuttons').show();
				$('#loader').html('');
				$("#step6").attr('disabled','disabled');
			}
		}
	});
}

function showMoreLikeQwords(Like_id,property_id)
{ 
	data ='property_id='+property_id+'&like_id='+Like_id;
	$.ajax({
		type: "POST",
		url: site_url + "listing/showMoreLikeQwords",
		data: data,
		success: function(data)			
		{
			openPopDiv('popupEdit');
			$("#popupEdit").html(data);		
		}
	});
}


function rets_data()
{ 
	err=0;
	detail_result
	$('#detailresult').hide();
	$('#detail_result').hide();
	$('#e_selection').html('<img src="images/loader.gif" align="absmiddle">');
	selection = $.trim($('#search').val());
	searchby =  $('input:radio[name="searchby"]:checked').val();
	if(selection.length <= 0)
	{ 
		$('#e_selection').html('Please enter selection id.');
		err=1;
	}

	if(err == 1 )
	{
		$('#retsdatalist').hide();
		return false;
	}

	data = 'selection='+selection+'&searchby='+searchby;
	$.ajax({
		type: "POST",
		url: site_url + "listing/rets_data_list",
		data: data,
		success: function (data) {
			if (data)
			{
				$("#detail_result").html(data);
				$('#detail_result').show();
				$('#e_selection').html('');
			}
			else
			{
				$("#Nodata").html("No Data Found");
				$('#detailresult').show();
				$('#e_selection').html('');
			}
		}
	});
}