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

				<td  id='<?php echo $row['Area_id'].'_area_name';?>' style="text-align:left;"><?php echo $row['Neighborhood'];?></td>
				<td><?php echo $row['Count'];?></td>
				<td>
					<!-- <a href="javascript:void(0);" class="pencil" onclick="addEditAreaNames('<?php echo $row['Area_id'];?>');openPopDiv('popupEdit');"></a>
					<a href="javascript:void(0);" onClick="deleteAreaName('<?php echo $row['Area_id'];?>','<?php echo $row['Neighborhood'];?>');" class="delete"></a> -->
					<a href="javascript:void(0);" class="pencil" onclick="addEditAreaNames('<?php echo $row['Area_id'];?>','<?php echo $row['Neighborhood'];?>','<?php echo $row['Count'];?>');openPopDiv('popupEdit');"></a>
					<a href="javascript:void(0);" onClick="deleteAreaName('<?php echo $row['Area_id'];?>','<?php echo $row['Neighborhood'];?>');" class="delete"></a>
				</td>
			</tr>
			<?php
						}
					}
			?>
		</table>
