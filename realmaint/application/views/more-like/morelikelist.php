<?php 
	//echo '<pre>';print_r($property); exit;
	$token_array=$property['token'];
	$Property_id= $property['Property_id'];
	$Location_id= $property['Location_id'];
	$ListingNumber= $property['ListingNumber'];
	$ListingId= $property['ListingId'];
	$PropertyType= $property['PropertyType'];
	$ListPrice= $property['ListPrice'];
	$SquareFeet= $property['SquareFeet'];
	$TotalBedrooms= $property['TotalBedrooms'];
	$TotalBathrooms= $property['TotalBathrooms'];
	$AveragePrice= $property['AveragePrice'];
	$MedianPrice= $property['MedianPrice'];
	$AveragePriceSqft= $property['AveragePriceSqft'];
	$MedianPriceSqft= $property['MedianPriceSqft'];
	$geodna=$property['geodna'];
	$GarageSpaces= $property['garagespaces'];
	
	$BasePricePerFoot=round($ListPrice/$SquareFeet);
	
	//Calculate Range
	$MinPrice = round($ListPrice -($ListPrice*0.20));
	$MaxPrice = round($ListPrice + ($ListPrice*0.20));
	$MinPricePerFt =  round($BasePricePerFoot - ($BasePricePerFoot*0.15));
	$MaxPricePerFt = round($BasePricePerFoot + ($BasePricePerFoot*0.15));
	$MinSqft = round($SquareFeet - ($SquareFeet*0.20));
	$MaxSqft = round($SquareFeet + ($SquareFeet*0.20));
	$MinBed = $TotalBedrooms-1;
	$MaxBed = $TotalBedrooms+1;
	$MinBath = $TotalBathrooms-1;
	$MaxBath = $TotalBathrooms+1;
?>
<div style="border:1px solid gray;padding:5px;margin:5px;">
	<div  style="border: 1px solid gray; padding:10px;">
		<Strong>Base Property</Strong>
		<span>
		<span class="red" id="loader"></span>
			<input type="button" name="search" id="search" value="go" size="17"  class="gobtn"  id ="go"  onClick="delete_more_listing(<?php echo $Property_id; ?>)"/>
		</span>
		
	</div>
	<br>
	<table border="0" cellspacing="0" cellpadding="0" class="data-table">
		<tr>
			<td width="130">Property_id</td>
			<td width="215"><?php echo $Property_id;?></td>
		</tr>
		<tr>
			<td width="130">MLS (ListingNumber)</td>
			<td width="215"><?php echo $ListingNumber;?></td>
		</tr>
		<tr>
			<td width="130">Location_id</td>
			<td id="Location_id" width="215"><?php echo $Location_id;?></td>
		</tr>
		<tr>
			<td width="130">ListingId</td>
			<td id="ListingId" width="215"><?php echo $ListingId;?></td>
		</tr>
		<tr>
			<td width="130">PropertyType</td>
			<td id="PropertyType" width="215"><?php echo $PropertyType;?></td>
		</tr>
		<tr>
			<td width="130">ListingPrice</td>
			<td width="215"><?php echo $ListPrice;?></td>
		</tr>
		<tr>
			<td width="130">SquareFeet</td>
			<td width="215"><?php echo $SquareFeet;?></td>
		</tr>
		<tr>
			<td width="130">BasePricePerFoot</td>
			<td id="BasePricePerFoot" width="215"><?php echo $BasePricePerFoot;?></td>
		</tr>
		<tr>
			<td width="130">Bedrooms</td>
			<td  id="Bedrooms" width="215"><?php echo $TotalBedrooms;?></td>
		</tr>
		<tr>
			<td width="130">Bathrooms</td>
			<td  id="Bathrooms" width="215"><?php echo $TotalBathrooms;?></td>
		</tr>
		<tr>
			<td width="130">Geodna</td>
			<td  id="Geodna" width="215"><?php echo $geodna;?></td>
		</tr> 
		<tr>
			<td width="130">GarageSpaces</td>
			<td  id="GarageSpaces" width="215"><?php echo $GarageSpaces;?></td>
		</tr> 
		<tr>
			<td width="130">MinPrice in $</td>
			<td id="MinPrice" width="215"><?php echo  $MinPrice;?></td>
		</tr> 
		<tr>
			<td  width="130">MaxPrice in $</td>
			<td id="MaxPrice" width="215"><?php echo  $MaxPrice;?></td>
		</tr>
		<tr>
			<td width="130">MinPricePerFt in $ </td>
			<td id="MinPricePerFt" width="215"><?php echo  $MinPricePerFt;?></td>
		</tr> 
		<tr>
			<td width="130">MaxPricePerFt in $ </td>
			<td id="MaxPricePerFt" width="215"><?php echo $MaxPricePerFt;?></td>
		</tr> 
		<tr>
			<td width="130">MinSqft</td>
			<td id="MinSqft" width="215"><?php echo $MinSqft;?></td>
		</tr> 
		<tr>
			<td  width="130">MaxSqft</td>
			<td id="MaxSqft"width="215"><?php echo $MaxSqft;?></td>
		</tr> 
		<tr>
			<td  width="130">MinBed</td>
			<td id="MinBed" width="215"><?php echo $MinBed;?></td>
		</tr> 
		<tr>
			<td width="130">MaxBed</td>
			<td  id="MaxBed" width="215"><?php echo $MaxBed;?></td>
		</tr> 
		<tr>
			<td width="130">MinBath</td>
			<td id="MinBath" width="215"><?php echo $MinBath;?></td>
		</tr>
		<tr>
			<td  width="130">MaxBath</td>
			<td id="MaxBath" width="215"><?php echo $MaxBath;?></td>
		</tr> 
		<tr>
			<td  width="130">AveragePrice</td>
			<td id="AveragePrice" width="215"><?php echo $AveragePrice;?></td>
		</tr> 
		<tr>
			<td  width="130">MedianPrice</td>
			<td id="MedianPrice" width="215"><?php echo $MedianPrice;?></td>
		</tr> 
		<tr>
			<td  width="130">AveragePriceSqft</td>
			<td id="AveragePriceSqft" width="215"><?php echo $AveragePriceSqft;?></td>
		</tr> 
		<tr>
			<td  width="130">MedianPriceSqft</td>
			<td id="MedianPriceSqft" width="215"><?php echo $MedianPriceSqft;?></td>
		</tr> 
	</table>
		<div class="headingblock">Base Property Jump & QWords data	</div>
		<table border="0" cellspacing="0" cellpadding="0" class="data-table">
		<tr>
			<th  width="130">QDNA</td>
			<th  width="130">Token</td>
			<th  width="130">QWords</td>
		</tr> 
		<?php foreach($token_array  as $token) { ?>
		<tr>
			<td id="QDNA" width="100"><?php echo $token['QDNA'];?></td>
			<td id="Token" width="100"><?php echo $token['Token'];?></td>
			<td id="QWords" width="500"><?php echo $token['QWords'];?></td>
		</tr> 
		<?php } ?>
	</table>
</div>
<div id="nextstepsbuttons" style="float:left;margin:5px; display:none;">
	<button   class="inputbtn" id ="best" Value="best" onClick="best(<?php echo $Property_id; ?>)">Best</button>
	<button  class="inputbtn" id ="step3" Value="step3" onClick="Garagespace(<?php echo $Property_id; ?>)">Garage Spaces</button> 
	<button   class="inputbtn" id ="step6" Value="step6" onClick="Polygonmatch(<?php echo $Property_id; ?>)">Polygon Match</button>
</div>



