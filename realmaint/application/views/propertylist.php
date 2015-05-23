<?php //echo '<pre>';print_r($property);?>
<div style="border:1px solid gray;padding:5px;margin:5px;">
	<div  style="border: 1px solid gray; padding:5px;">
		<Strong>Property Listing</Strong>
		<span style="border: 1px solid gray;padding:2px;"><?php echo $property['PropertyType'];?></span>
		<span  style="border: 1px solid gray;padding:2px;"><?php echo $property['Status'];?></span>
	</div>
	<br>
	<table border="0" cellspacing="0" cellpadding="0" class="data-table">
		  <tr>
			<td width="130">Property_id</td>
			<td width="215" ><?php echo $property['Property_id'];?></td>
		  </tr>
		    <tr>
			<td width="130">ListingNumber</td>
			<td width="215"><?php echo $property['ListingNumber'];?></td>
		  </tr>
		    <tr>
			<td width="130">ListingId</td>
			<td width="215"><?php echo $property['ListingId'];?></td>
		  </tr>
		    <tr>
			<td width="130">ListingDate</td>
			<td width="215"><?php echo $property['ListingDate'];?></td>
		  </tr>
		    <tr>
			<td width="130">ListingPrice</td>
			<td width="215"><?php echo $property['ListPrice'];?></td>
		  </tr>
		    <tr>
			<td width="130">CloseDate</td>
			<td width="215"><?php echo $property['CloseDate'];?></td>
		  </tr>
		    <tr>
			<td width="130">ClosePrice</td>
			<td width="215"><?php echo $property['ClosePrice'];?></td>
		  </tr>
		    <tr>
			<td width="130">Location_id</td>
			<td width="215">
				<?php echo $property['Location_id'];?> <input type="button" class="inputbtn" value="Show" onClick="listLocation();selectNav('location-nav');"/>
			</td>
		  </tr>
		    <tr>
			<td width="130">FullAddress</td>
			<td width="215"><?php echo $property['FullAddress'];?></td>
		  </tr>
		    <tr>
			<td width="130">UnitNumber</td>
			<td width="215"><?php echo $property['UnitNumber'];?></td>
		  </tr>
		    <tr>
			<td width="130">BuildingNumber</td>
			<td width="215"><?php echo $property['BuildingNumber'];?></td>
		  </tr>
		    <tr>
			<td width="130">Style</td>
			<td width="215"><?php echo $property['Style'];?></td>
		  </tr>  <tr>
			<td width="130">Architecture</td>
			<td width="215"><?php echo $property['Architecture'];?></td>
		  </tr>  <tr>
			<td width="130">SquareFeet</td>
			<td width="215"><?php echo $property['SquareFeet'];?></td>
		  </tr>
		    <tr>
			<td width="130">YearBuilt</td>
			<td width="215"><?php echo $property['YearBuilt'];?></td>
		  </tr>
		    <tr>
			<td width="130">Bedrooms</td>
			<td width="215"><?php echo $property['TotalBedrooms'];?></td>
		  </tr>
		   <tr>
			<td width="130">Bathrooms</td>
			<td width="215"><?php echo $property['TotalBathrooms'];?></td>
		  </tr>
		   <tr>
			<td width="130">CarStorage</td>
			<td width="215"><?php echo $property['CarStorage'];?></td>
		  </tr>
		   <tr>
			<td width="130">TotalParking</td>
			<td width="215"><?php echo $property['TotalParking'];?></td>
		  </tr>
		   <tr>
			<td width="130">LotSizeSqft</td>
			<td width="215"><?php echo $property['LotSizeSqft'];?></td>
		  </tr>
		   <tr>
			<td width="130">LotSizeAcres</td>
			<td width="215"><?php echo $property['LotSizeAcres'];?></td>
		  </tr>
		   <tr>
			<td width="130">SellerType</td>
			<td width="215"><?php echo $property['SellerType'];?></td>
		  </tr>
		   <tr>
			<td width="130">ListOffice_id</td>
			<td width="215">
				<?php echo $property['ListOffice_id'];?> <input type="button" class="inputbtn" value="Show" onClick="listOffice();selectNav('office-nav');"/>
			</td>
		  </tr>
		   <tr>
			<td width="130">ListAgent_id</td>
			<td width="215"><?php echo $property['ListAgent_id'];?></td>
		  </tr>
		   <tr>
			<td width="130">Modified</td>
			<td width="215"><?php echo date("F j, Y, g:i a", strtotime($property['Modified'])) ;?></td>
		  </tr>
		   <tr>
			<td width="130">Entered</td>
			<td width="215"><?php echo date("F j, Y, g:i a", strtotime($property['Entered'])) ;?></td>
		  </tr>
	</table>
</div>