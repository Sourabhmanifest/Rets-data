<div style="border:1px solid gray;margin:5px;">
	<div style="border-bottom:1px solid gray;padding:5px;">
		<Strong>Related Location Records</Strong>
		<span style="border: 1px solid gray;padding:2px;"><Strong><?php echo count($real_location);?></Strong></span>
	</div>
	<?php
	foreach($real_location as $location)
	{
	?>
	<div style="padding:5px;border-bottom: 1px solid gray;">		
		<div  style="margin:10px;">
			<input type="radio" value="1" name="property">
			<span  style="border: 1px solid gray;padding:2px;width:20px;"><?php echo $location['StreetNumber'];?></span>
			<span  style="border: 1px solid gray;padding:2px;"><?php echo $location['StreetName'];?> <?php echo $location['StreetSuffix'];?> <?php echo $location['StreetDirSuffix'];?></span>
			<span  style="border: 1px solid gray;padding:2px;"><?php echo $location['City'];?></span>
			<span  style="border: 1px solid gray;padding:2px;"><?php echo $location['PostalCode'];?></span>
			<span  style="border: 1px solid gray;padding:2px;"><?php echo $location['Subdivision'];?></span>
		</div>
		<div style="margin:10px; font-weight: bold;">
		<span  style="border: 1px solid gray;padding:2px;">Id#: <?php echo $location['Location_id'];?></span>
		<span  style="border: 1px solid gray;padding:2px;">Unit#: <?php echo $location['UnitNumber'];?> Building#: <?php echo $location['BuildingNumber'];?></span>
		<span  style="border: 1px solid gray;padding:2px;">Lat: <?php echo $location['Latitude'];?>  Long: <?php echo $location['Longitude'];?></span>					
		</div>
	</div>
	<?php
	}
	?>
</div>