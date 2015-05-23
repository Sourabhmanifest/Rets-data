<?php
//echo '<pre>'; print_r( $user);exit;
if(isset($user))
{ 
	 $uid=$user['User_id'];
	 $username=$user['Username'];
	 $fullname=$user['Fullname'];
	 $email=$user['Email'];
	 $role=$user['Role'];
	 $password=$user['Password'];
}
else
{
	 $uid="";
	 $username="";
	 $fullname="";
	 $email="";
	 $role="";
	 $password="";
}  
?>

<h2><?php if($User_id) echo 'Update';else echo 'Add';?> User Account  <?php if($uid) echo "<span style='float:right;'>User Id:  $uid</span>";?></h2>
	<div class="content">
		<?php echo form_open('listing/saveUser/'.$User_id,'name="form_add_edit_user"  onSubmit="return saveUser('.$User_id.');"');?>
			<div style="float: left;margin-bottom: 15px;width:100%">
				<table class="content-table" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td>Username: </td>
							<td><input name="uname" id="uname" value="<?php echo $username;?>" onBlur="checkUserValidation(<?php echo $User_id;?>);">
							<div class="red" id="e_uname"></div>
						</td>
					</tr>
					<tr>
						<td>Fullname:</td>
							<td><input name="fullname" id="fullname" value="<?php echo $fullname;?>" style="width: 200px;" />
							<div class="red" id="e_fullname"></div>
						</td>
					</tr>
					<tr>
						<td>Email:</td>
							<td><?php if($uid) echo $email,'<input type="hidden" name="email" id="email" value="'.$email.'" >';else echo '<input  type="text" name="email" id="email" value="" style="width: 250px;" onBlur="checkEmailValidation('.$User_id.');">'; ?>
							<div class="red" id="e_email"></div>
						</td>
					</tr>
					<tr>
						<td>New Password: </td>
							<td><input type="password" name="newpwd" id="newpwd" value="" ><?php if($uid) echo "<span style='margin-left: 8px;'>CRC:#  $password</span>";?>
							<div class="red" id="e_newpwd"></div>
						</td>
					</tr>
					<tr>
						<td>Role:</td>
						<td><select name="role" id="role">
							<option value="system">system</option>
						</td>
					</tr>
				</table>
			</div>
			<div class="popupbtn" style="clear:both;">
				<table border="0" cellspacing="20" cellpadding="0">
					<tr>
						<td>
							<button type="submit"  name="save" class="savebtn" value="Save">Save</button>
						</td>
						<td ><span class="btn" href="javascript:void(0);" onclick="closePopDiv('popupEdit');">Cancel</span></td>
					</tr>
				</table>
			</div>
		</form>
	</div>