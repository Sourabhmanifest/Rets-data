<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>REal-MARKABLE</title>
<link rel="shortcut icon" href="images/favicon.png" type="image/png"/>

<script type="text/javascript">
	var site_url = '<?php echo site_url();?>';
	var base_url = '<?php echo base_url();?>';
	var current_url = '<?php echo current_url();?>';
</script>

<base href="<?php echo base_url();?>">


<script type="text/javascript" src="javascript/jquery-1.10.2.js"></script>
<script type="text/javascript" src="javascript/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="javascript/functions.js"></script>
<script type="text/javascript" src="javascript/popUpDivJs.js"></script>

<link href="css/style.css" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

</head>

<body>
<!-- wrapper div start here-->
<div id="wrapper">

<!-- header div start here-->
<div id="header">
	<?php
	if($this->session->userdata('User_id')!='' && $this->session->userdata('email')!='')
	{
	?>
		<div class="top-left w46">
			<?php echo anchor('','<span id="category"><h1>Home</h1></span>');?>
		</div>
		<div class="top-left" style="margin: 0 400px;">
			<img src="images/Logo.jpg" width="220" height="45"/>
		</div>
		<div class="top-right">
			<?php echo anchor('listing/logout','<span class="logout">Logout :)</span>');?>
		</div>
	<?php
	}
	else
	{
	?>
		<div class="top-left w46">
			<h1></h1>
		</div>
		<div class="top-left" style="margin: 0 400px;">
			<img src="images/Logo.jpg" width="220" height="45"/>
		</div>	
		<div class="top-right">
			<span><?php echo anchor('#',':)','class="logout"');?></span>
		</div>
	<?php
	}
	?>
</div>
<!-- header div ends here-->
 