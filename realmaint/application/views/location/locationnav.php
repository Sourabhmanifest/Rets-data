<?php 
	if (! count($polygon_listing) )
	{
		$Polygon_id='no data found';
	}
	else
	{
		$Polygon_id=$polygon_listing['Polygon_id'];
	}

?>

<div style="border:1px solid gray;padding:10px;margin:5px;">
	<span id="main-tabs">
		<span onClick="listStreet('<?php echo $location['StreetName'];?>','StreetName');selectNav(this);" class="selected">STREET</span>
		<span onClick="listStreet('<?php echo $Polygon_id; ?>','Polygon_id');selectNav(this);">POLYGON</span>
		<span onClick="listStreet('<?php echo $location['Subdivision'];?>','Subdivision');selectNav(this);">SUBDIVISION</span>
		<span onClick="listStreet('<?php echo $location['Latitude']."~~".$location['Longitude'];?>','Latitude');selectNav(this);">LAT/LONG</span>
	</span>
	<span >
		<select id="lat-long-ft">
			<option>9</option>
			<option>19</option>
			<option>28</option>
		</select>
		<b>ft.</b>
	</span>
</div>
<div id="street-info">
	<?php $this->load->view('location/streetlist');?>
</div>
