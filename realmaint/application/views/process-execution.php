	<?php //echo '<pre>';print_r($properties);exit;?> 

	<div style="line-height:65px; text-align:center;">
	<strong><h3> Process Execution Table Detail</h3></strong></div>
	<table border="0" cellspacing="0" cellpadding="0" class="data-table" id='pe-table'>
		 <tr>
			<th>Process_id</th>
			<th>Process_Name</th>
			<th>Scheduled</th>
			<th>Last Executed</th>
			<th>Executed On</th>
			<th>Status</th>
			<th>EXECUTE BUTTON</th>
			
		 </tr>
		 <?php $stats=0;?>
		 <?php foreach($properties as $property)
		 {
			 $stats++;
				$source=$property["Source"];
				if($source=="run.sh")
				 {
					$source="rcode/run.sh";
				 }
				$process_Name=$property['Process_Name'];
				$last_execution_date=$property["Executed"];
				$pd=strtotime($last_execution_date);
				$now=time();
				$diff = abs($now - $pd);
				$days = floor(($diff/(60*60*24)));
				if($days==0)
				{
					$last_execute='Today';
				}
				else 
				{
					$last_execute=$days.' Days ago';					
				}

				if($property['Status']==0)
				{
					$Status= 'Started';
				}
				elseif($property['Status']==1)
				{
					$Status= 'Completed';
				}
				elseif($property['Status']==-1)
				{
					$Status= 'Failed';
				}
			
			 ?>
		 <tr>
			<td><?php echo $property['Process_id'];?></td>
			<td><?php echo $process_Name;?></td>
			<td><?php echo $property['Schedule'];?></td>
			<td><?php echo $last_execute;?></td>
			<td><?php echo $property['Executed'];?></td>
			<td id='<?php echo $stats;?>'><?php echo $Status?></td>
			<td>
					<!-- <input class="itemdiv" type='button' value="Execute Process" 
					onClick="processExecute('<?php echo $source;?>, <?php echo $stats; ?>');"> -->
					<input class="itemdiv" type='button' value="Execute Process" 
					onClick="processExecute('<?php echo $source;?>');">
			</td> 
		 </tr>
		 <?php } ?>
	</table>
</div>
<!-- <input class="itemdiv" type='button' value="Execute Process" 
					onClick="if(confirm('Are you sure you want to execute <?php echo $process_Name; ?>?'))
					process_execution_status('<?php echo $source; ?>','<?php echo $property['Process_id'];?>');
					else
						<?php echo $source; ?>">
									 -->
					<!-- window.open(url='http://realmarkable.com/cronjobs/<?php echo $source?>');
					else window.close(); -->