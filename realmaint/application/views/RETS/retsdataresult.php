<?php  //echo '<pre>';print_r($property); exit; ?>

	<div style="border:1px solid gray;padding:5px;margin:5px;">
	<div  style="border: 1px solid gray; padding:5px;">
		<Strong>RETS Results : </Strong>
		<span  class="red" id="_loader"></span>
	</div>
	
	<br>
	<table id="retsdataresulttable" class='data-table' border="0" cellspacing="0" cellpadding="0" class="data-table">
		<tr>
			<th>KEY</th>
			<th>VALUE</th>
		</tr>
		<?php 
			$count=0;
			foreach($property as $property_row) 
			{
				foreach($property_row as $key =>$value)
				{
					//echo '<pre>';print_r($key); 
					?>
					<tr>
						<td ><?php echo $key; ?></td>
						<td><?php echo $value; ?></td>
					</tr>
					<?php 
				}
			}	?>
				
	</table>
</div>
