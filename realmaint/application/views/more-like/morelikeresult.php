<?php //echo '<pre>';print_r($property); exit;
if($property=="No Match Found")
{
	echo $property;
}
else {	?>
<div style="border:1px solid gray;padding:5px;margin:5px;">
	<div  style="border: 1px solid gray; padding:5px;">
		<Strong>More Like Listing : </Strong>
		<span  class="red" id="_loader"></span>
	</div>
	
	<br>
	<table id="morelikelistingtable" class='data-table' border="0" cellspacing="0" cellpadding="0" class="data-table">
		<tr>
			<th>No.</th>
			<th>Property id</th>
			<th>SqFt</th>
			<th>Bed rooms</th>
			<th>Bath rooms</th>
			<th>Garage Spaces</th>
			<th>Proxi mity (miles)</th>
			<th class="diffcolor">PF</th>
			<th class="diffcolor">AP</th>
			<th class="diffcolor">MP</th>
			<th class="diffcolor">APF</th>
			<th class="diffcolor">MPF</th>
			<th>Match Percent</th>
			<th>Jump Score</th>
			<th>Value Score</th>
			<th>Score</th>
		</tr>
		<?php 
			$count=0;
			foreach($property as $_property) { 
				
				// convert meters proximity in miles.
				if($_property['Proximity']>0)
				{
					$Proximity=round($_property['Proximity']/1609.344);
				}
				else
				{
					$Proximity=0;
				}?>
				<!-- Display data in table-->
				<tr>
					<td ><?php echo ++$count; ?></td>
					<td  onClick="showMoreLikeQwords('<?php echo $_property['Like_id'];?>','<?php echo $_property['Property_id'];?>');" style="cursor:pointer;"><?php echo $_property['qProp_id'];?></td>
					<td ><?php echo $_property['SquareFeet'];?></td>
					<td><?php echo $_property['Bedrooms'];?></td>
					<td ><?php echo $_property['Bathrooms'];?></td>
					
				
					<?php if($_property['GarageSpaces']!=0) { ?>
						<td class ="yellow" id="gs<?php echo $_property['qProp_id'];?>"><?php echo $_property['GarageSpaces'];?></td>
					<?php  } 
					else { ?>
						<td id="gs<?php echo $_property['qProp_id'];?>"><?php echo $_property['GarageSpaces'];?></td>
					<?php } ?>

					<td id="pm<?php echo $_property['qProp_id'];?>"><?php echo $Proximity;?></td>
					
					<?php if($_property['BasePerFT']==0) { ?>
						<td class ="lightgreen"><?php echo $_property['BasePerFT'];?></td>
					<?php  } ?>
					<?php if($_property['BasePerFT']==-1) { ?>
						<td class ="pink"><?php echo $_property['BasePerFT'];?></td>
					<?php  } ?>
					<?php if($_property['BasePerFT']==1) { ?>
						<td class ="yellow"><?php echo $_property['BasePerFT'];?></td>
					<?php  } ?>
					
					<?php if($_property['PolyAvgPrc']==0) { ?>
						<td class ="lightgreen"><?php echo $_property['PolyAvgPrc'];?></td>
					<?php  } ?>
					<?php if($_property['PolyAvgPrc']==-1) { ?>
						<td class ="pink"><?php echo $_property['PolyAvgPrc'];?></td>
					<?php  } ?>
					<?php if($_property['PolyAvgPrc']==1) { ?>
						<td class ="yellow"><?php echo $_property['PolyAvgPrc'];?></td>
					<?php  } ?>
					
					<?php if($_property['PolyMedPrc']==0) { ?>
						<td class ="lightgreen"><?php echo $_property['PolyMedPrc'];?></td>
					<?php  } ?>
					<?php if($_property['PolyMedPrc']==-1) { ?>
						<td class ="pink"><?php echo $_property['PolyMedPrc'];?></td>
					<?php  } ?>
					<?php if($_property['PolyMedPrc']==1) { ?>
						<td class ="yellow"><?php echo $_property['PolyMedPrc'];?></td>
					<?php  } ?>

					<?php if($_property['PolyAvgPerft']==0) { ?>
						<td class ="lightgreen"><?php echo $_property['PolyAvgPerft'];?></td>
					<?php  } ?>
					<?php if($_property['PolyAvgPerft']==-1) { ?>
						<td class ="pink"><?php echo $_property['PolyAvgPerft'];?></td>
					<?php  } ?>
					<?php if($_property['PolyAvgPerft']==1) { ?>
						<td class ="yellow"><?php echo $_property['PolyAvgPerft'];?></td>
					<?php  } ?>
					
					<?php if($_property['PolyMedPerft']==0) { ?>
						<td class ="lightgreen"><?php echo $_property['PolyMedPerft'];?></td>
					<?php  } ?>
					<?php if($_property['PolyMedPerft']==-1) { ?>
						<td class ="pink"><?php echo $_property['PolyMedPerft'];?></td>
					<?php  } ?>
					<?php if($_property['PolyMedPerft']==1) { ?>
						<td class ="yellow"><?php echo $_property['PolyMedPerft'];?></td>
					<?php  } ?>
					<td ><?php echo $_property['MatchPercent'];?></td>
					<td ><?php echo $_property['JumpScore'];?></td>
					<td ><?php echo $_property['ValueScore'];?></td>
					<td ><?php echo $_property['Score'];?></td>
				</tr>
		<?php } ?>
	</table>
</div>


<!--PopUp : Start-->
<div id="popupEdit"></div>
<!--PopUp : End-->

<?php 
}
?>