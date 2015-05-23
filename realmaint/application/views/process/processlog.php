<div id="content">


	<div style="border:1px solid grey; padding:10px" >
		<table cellspacing="0" cellpadding="0" border="0" class="data-table" width="100%">
			<tr>
				<th style="width: 75px;"></th>
				<th>ID</th>
				<th>Process</th>
				<th>Last Execute</th>
				<th>Status</th>
			</tr>
			<?php
				foreach($processes as $process)
				{
					if($process['Status']==1){
						$status= 'Success';
					}
					else{
						$status='Failed';
					}
				
			?>
			<tr>
				<td><input type="radio" name="statusbyid" onClick="statusProcess();" value="<?php echo $process['Process'];?>"><span id="<?php echo $process['Process'];?>"></td>
				<td><?php echo $process['id'];?></span></td>
				<td><?php echo $process['Process'];?></td>
				<td><?php echo $process['Executed'];?></td>
				<td><?php echo $status;?></td>
			</tr>
			<?php
				}
			?>
		</table>
	</div>
	
	<div id="process-log-status"></div>

</div>