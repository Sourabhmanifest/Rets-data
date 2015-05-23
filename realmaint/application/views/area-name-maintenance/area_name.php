<div id="content">
	<div style="border:1px solid grey; padding:10px" >
		<table cellspacing="0" cellpadding="0" border="0"  width="100%">
			<tr>				
				<td style="width:145px;"> <strong>Order By: </strong>
				<select id='orderBy' onchange='orderBy("a")' >
				<option value='a' selected>A</option>
				<option value='b'>B</option> 
				<option value='c'>C</option> 
				<option value='d'>D</option> 
				<option value='e'>E</option> 
				<option value='f'>F</option> 
				<option value='g'>G</option> 
				<option value='h'>H</option> 
				<option value='i'>I</option> 
				<option value='j'>J</option> 
				<option value='k'>K</option> 
				<option value='l'>L</option> 
				<option value='m'>M</option> 
				<option value='n'>N</option> 
				<option value='o'>O</option> 
				<option value='p'>P</option> 
				<option value='q'>Q</option> 
				<option value='r'>R</option> 
				<option value='s'>S</option> 
				<option value='t'>T</option> 
				<option value='u'>U</option> 
				<option value='v'>V</option> 
				<option value='w'>W</option> 
				<option value='x'>X</option> 
				<option value='y'>Y</option> 
				<option value='z'>Z</option> 
				<option value='#'>#</option></select>
				<span class="red" id="success_msg"><?php if(!$area_name) echo "No Data Found";?></span>
				<span id="e_search_keyword"></span></td>
			</tr>
		</table>
	</div>

	<div style="border:1px solid grey; padding:10px;margin-top:10px;" id='areaDisplay'>
		<table cellspacing="0" cellpadding="0" border="0" class="data-table" width="100%">
			<tr>
				<!--<th style="width: 17px;"></th>-->
				<th>Area_id</th>
				<th>Area Name</th>
				<th>Count</th>
				<th style="width:145px;">Edit&nbsp;&nbsp;|&nbsp;&nbsp;Delete</th>
			</tr>
				<?php
					//echo '<pre>'; print_r( $area_name);exit;
					if(count($area_name)>0)
					{
						$start = 0;
						$count = $start;
						foreach($area_name as $row)
						{
							$count++;
				?>

			<tr  <?php if($count%2 != 0) echo 'class="blue-bg"';?> id="<?php echo $row['Area_id'];?>">
				<!--<td><input type="radio" name="userbyid" onClick="" value=""></td>-->
				<td><?php echo $row['Area_id'];?></td>
				<td id='<?php echo $row['Area_id'].'_area_name';?>' style="text-align:left;"><?php echo $row['Neighborhood'];?></td>
				<td><?php echo $row['Count'];?></td>
				<td>
					<a href="javascript:void(0);" class="pencil" onclick="addEditAreaNames('<?php echo $row['Area_id'];?>','<?php echo $row['Neighborhood'];?>','<?php echo $row['Count'];?>');openPopDiv('popupEdit');"></a>
					<a href="javascript:void(0);" onClick="deleteAreaName('<?php echo $row['Area_id'];?>','<?php echo $row['Neighborhood'];?>');" class="delete"></a>
				</td>
			</tr>
			<?php
						}
					}
			?>
		</table>
	</div>
</div>
<script>
$('#orderBy').val(function() 
{
	return $(this).find('option:eq(0)').attr('value')
});
</script>
<!--PopUp : Start-->
<div id="popupEdit"></div>
<!--PopUp : End-->