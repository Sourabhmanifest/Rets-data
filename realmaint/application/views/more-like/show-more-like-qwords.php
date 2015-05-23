<?php //echo '<pre>';print_r($property_array); exit;?>
<h2 style="text-align:center">More like Qwords Matched Tokens </h2>
<!-- <h2 style="text-align:center">Matched Tokens</h2> -->
	<div class="content">
			<div style="margin-bottom: 15px;margin-top: 15px;width:100%;text-align:-moz-center">
			<?php 
			if(count($property_array))
			{
			?>
				<table class="data-table" cellspacing="0" cellpadding="0" border="0" style="text-align:center">
					<tr>
						<th>QDNA</td>
						<th >QWords</td>
						<th >Token</td>
					</tr>
					<?php foreach($property_array as $property) { ?>
						<tr>
							<td><?php echo $property['QDNA'];?></td>
							<td><?php echo $property['QWords'];?></td>
							<td><?php echo $property['Token'];?></td>
						</tr>
					<?php } ?>
				</table>
				<?php 
				}
				else
				echo '<p class="red">!! No Token Match found !!</p>';
				?>
			</div>
	</div>