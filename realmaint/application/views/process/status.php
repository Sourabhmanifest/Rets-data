<div  style="border:1px solid grey; padding:10px;margin-top:10px;">
	<table cellspacing="0" cellpadding="0" border="0" class="data-table" width="100%">
		<tr>
			<th>ID</th>
			<th>DateTime</th>
			<th>Process</th>
			<th>Step</th>
			<th>Message</th>
			<th>Status</th>
		</tr>
		<?php 
			foreach($statuslog as $log)
			{
				if($log['Status']==1){
					$Status= 'Success';
				}
				else{
					$Status='Failed';
				}
			
		?>
		<tr>
			<td><?php echo $log['Log_ID'];?></td>
			<td><?php echo $log['DateTime'];?></td>
			<td><?php echo $log['Process'];?></td>
			<td><?php echo $log['Step'];?></td>
			<td><?php echo $log['Message'];?></td>
			<td><?php echo $Status;?></td>
			
		</tr>
		<?php
			}
		?>
	</table>
</div>