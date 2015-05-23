<!-- this page is for login -->

<div id="content">
  <?php	echo form_open('listing/login','name="signInForm" save="save"'); ?>
  <table border="0" cellspacing="0" cellpadding="0" class="data-table" style="width:35%;margin:auto;margin-top:100px;">
		  <tr>
				<th colspan="2">UserLogin</th>
		  </tr>
		  <tr>
				<td width="30%"><strong>Email</strong></td>
				<td width="70%" colspan="4">
					<input name="email" type="text" id="email" size="30" value="<?php echo set_value('email'); ?>" class="input"/>
					<span class="red"><?php echo form_error('email'); ?></span>
				</td>
		  </tr>
		  <tr class="blue-bg">
				<td><strong>Password</strong></td>
				<td>
					<input name="password" type="password" id="password" size="30" value="" class="input" />
					<span class="red"><?php echo form_error('password'); ?></span>
				</td>
		  </tr>
		  <tr>
			
			<td colspan="2">
				<div class="center">
					<label><input type="submit" name="button" id="button" value="Login" class="inputbtn" /></label>
				</div>
			</td>
		  </tr>
	</table>
  </form>
</div>
