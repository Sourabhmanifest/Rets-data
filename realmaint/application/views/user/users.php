
<div id="content">
	<div style="border:1px solid grey; padding:10px" >
		<table cellspacing="0" cellpadding="0" border="0"  width="100%">
			<tr>
				
				<td style="width:145px;"><span class="green" id="success_msg"><?php if($msg) echo "User $msg successfully";?></span><span class="btn" style="float:right;" onClick="addEditUser('');openPopDiv('popupEdit');" href="javascript:void(0);">Add User</span></td>
			</tr>
		</table>
	</div>

	<div style="border:1px solid grey; padding:10px;margin-top:10px;" >
		<table cellspacing="0" cellpadding="0" border="0" class="data-table" width="100%">
			<tr>
				<!--<th style="width: 17px;"></th>-->
				<th>ID</th>
				<th>Username</th>
				<th>Full Name</th>
				<th>Email</th>
				<th>Role</th>
				<th>Password</th>
				<th style="width:145px;">Edit&nbsp;&nbsp;|&nbsp;&nbsp;Delete</th>
				</tr>
				<?php
					//echo '<pre>'; print_r( $users);exit;
					if(count($users)>0)
					{
						$start = 0;
						$count = $start;
						foreach($users as $row)
						{
							$count++;
				?>

			<tr  <?php if($count%2 != 0) echo 'class="blue-bg"';?> id="user_<?php echo $row['User_id'];?>">
				<!--<td><input type="radio" name="userbyid" onClick="" value=""></td>-->
				<td><?php echo $row['User_id'];?></td>
				<td><?php echo $row['Username'];?></td>
				<td><?php echo $row['Fullname'];?></td>
				<td><?php echo $row['Email'];?></td>
				<td><?php echo $row['Role'];?></td>
				<td><?php echo $row['Password'];?></td>
				<td>
					<a href="javascript:void(0);" class="pencil" onclick="addEditUser('<?php echo $row['User_id'];?>');openPopDiv('popupEdit');">

					<a href="javascript:void(0);" onClick="deleteUser('<?php echo $row['User_id'];?>');" class="delete"></a>
				</td>
			</tr>
			<?php
						}
					}
			?>
		</table>
	</div>


</div>

<!--PopUp : Start-->
<div id="popupEdit">Hello</div>
<!--PopUp : End-->