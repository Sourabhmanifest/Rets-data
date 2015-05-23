<?php
//echo '<pre>'; print_r( $Area_Name);exit;
if(isset($Area_Name))
{ 
	 $Area_id=$Area_Name['Area_id'];
	 $Neighborhood=$Area_Name['Neighborhood'];
	 $Count=$Area_Name['Count'];
}
else
{
	 $Area_id="";
	 $AreaName="";
	 $Count="";
}  
?>

<h2><?php if($Area_id) echo 'Update';else echo 'Add';?> Area Name  <?php if($Area_id) echo "<span style='float:right;'>Area ID:  $Area_id</span>";?></h2>
	<div class="content">
		<!-- <?php// echo form_open('listing/saveAreaNames/','name="form_add_edit_user" ');?> -->
			<div style="float: left;margin-bottom: 15px;width:100%">
				<table class="content-table" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td>AreaName:  </td>
							<td><input name="AreaName" id="AreaName" value="<?php echo $Neighborhood;?>" style="width: 200px;" />
							<div class="red" id="e_fullname"></div>
						</td>
					</tr>
					
					<tr>
						<td>Count: </td>
							<td><input type="text" name="Count" id="Count" value="<?php echo $Count;?>" readonly>
							<input type="hidden" name="Area_id" id="Area_id" value="<?php echo $Area_id;?>" readonly>
						</td>
					</tr>
				</table>
			</div>
			<div class="popupbtn" style="clear:both;">
				<table border="0" cellspacing="20" cellpadding="0">
					<tr>
						<td>
							<button type="submit"  id="save" name="save" class="savebtn" value="Save"  onclick="saveAreaNames();closePopDiv('popupEdit');">Save</button>
						</td>
						<td ><span class="btn" href="javascript:void(0);" onclick="closePopDiv('popupEdit');">Cancel</span></td>
					</tr>
				</table>
			</div>
		</form>
	</div>