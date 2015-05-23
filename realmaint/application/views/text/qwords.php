<?php //echo '<pre>';print_r($qwords); exit;?>
<div style="border:1px solid gray;margin:5px;">
	<div style="border-bottom:1px solid gray;padding:5px;">
		<Strong>QWords</Strong>
		<span  style="border: 1px solid gray;padding:2px;"><?php echo count($qwords); ?></span>
	</div>

	<div style="border-bottom:1px solid gray;padding:5px;">
		<Strong>List Real Qwords</Strong>
	</div>
	<div style="padding:5px;">
		<table border="0" cellspacing="0" cellpadding="0" class="data-table">
			<tr>
				<th width="130">QDNA</th>
				<th width="130">QWords</th>
				<th width="130">Token</th>
				<th width="215">Modified</th>
			  </tr>
			
			<?php 
			foreach($qwords as $qwords_row)
			{
			?>
			  <tr>
				<td width="130">
					<div style="border: 1px solid gray;padding:2px;width: 50px;text-align: center;margin: 5px;"><?php echo $qwords_row['QDNA']; ?></div>
					<div style="border: 1px solid gray;padding:2px;width: 50px;text-align: center;margin: 5px;"><?php echo $qwords_row['Count']; ?></div>
				</td>
				<td width="215"><?php echo $qwords_row['QWords']; ?></td>
				<td width="215"><?php echo $qwords_row['Token']; ?></td>
				<td width="130">
					<div style="border: 1px solid gray;padding:2px;width: 100px;text-align: center;margin: 5px;"><?php echo date("F j, Y, g:i a", strtotime($qwords_row['Modified'])) ;?></div>
					<div style="padding:2px;width: 50px;text-align: center;margin: 5px;"><?php if ($qwords_row['Public']) echo 'Public'; else echo 'Private'; ?></div>
				</td>
			  </tr>
			<?php 
			}
			?>
		  </table>
	</div>
</div>