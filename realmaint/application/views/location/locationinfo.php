<?php 
	if(empty($polygon_listing))
	{
		$polygon_listing['Polygon_id']='no data found';
	}?>
<div style="border:1px solid gray;margin:5px;">
	<div style="border-bottom:1px solid gray;padding:5px;">
		<Strong>Real Location</Strong>
		<span style="border: 1px solid gray;padding:2px;"><Strong><?php echo $location['PropertyType'];?></Strong></span>
		<span>Polygon </span>
		<span  style="border: 1px solid gray;padding:2px;"><?php echo $polygon_listing['Polygon_id'];?></span>
		<span  style="border: 1px solid gray;padding:2px;"><?php echo $location['VerifiedOn'];?></span>
		<span  style="border: 1px solid gray;padding:2px;"><Strong><?php echo $location['Location_id'];?></Strong></span>
	</div>
	<div style="padding:5px;">		
		<div  style="margin:10px;">
			<span  style="border: 1px solid gray;padding:2px;width:20px;"><?php echo $location['StreetNumber'];?></span>
			<span  style="border: 1px solid gray;padding:2px;"><?php echo $location['StreetName'];?> <?php echo $location['StreetSuffix'];?> <?php echo $location['StreetDirSuffix'];?></span>
			<span  style="border: 1px solid gray;padding:2px;"><?php echo $location['City'];?></span>
			<span  style="border: 1px solid gray;padding:2px;"><?php echo $location['PostalCode'];?></span>
			<span  style="border: 1px solid gray;padding:2px;"><?php echo $location['Subdivision'];?></span>
		</div>
		<div style="margin:10px; font-weight: bold;">
		<span  style="border: 1px solid gray;padding:2px;">Id#: <?php echo $location_listing['Location_id'];?></span>
		<span  style="border: 1px solid gray;padding:2px;">Unit#: <?php echo $location_listing['UnitNumber'];?> Building#: <?php echo $location_listing['BuildingNumber'];?></span>
		<span  style="border: 1px solid gray;padding:2px;">Lat: <?php echo $location['Latitude'];?>  Long: <?php echo $location['Longitude'];?></span>					
		</div>
	</div>
</div>