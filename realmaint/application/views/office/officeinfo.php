<div style="border:1px solid gray;margin:5px;">
	<div style="border-bottom:1px solid gray;padding:5px;">
	<?php
	if($office)
	{
	?>
		<table border="0" cellspacing="5" cellpadding="0">
			<tr>
				<td height="30"><Strong>Office</Strong></td>
				<td  width="50"><div  style="border: 1px solid gray;padding:2px;"><?php echo $office['Office_id'];?></div></td>
				<td  width="280"><div  style="border: 1px solid gray;padding:2px;"><?php echo $office['OfficeName'];?></div></td>
				<td  width="200"><div  style="border: 1px solid gray;padding:2px;"><?php echo $office['OfficePhone'];?></div></td>
				<td  width="100"><div  style="border: 1px solid gray;padding:2px;"><?php echo $office['ListOfficeId'];?></div></td>
			</tr>
		</table>
	<?php
	}
	else
	{
		echo 'No data found';
	}
	?>
	</div>
	<div style="padding:5px;">
	<?php
	if($agent)
	{
	?>
		<table border="0" cellspacing="5" cellpadding="0">
			<tr>
				<td height="30" rowspan="3"  valign="top"><Strong>Agent</Strong></td>
				<td  width="50"><div  style="border: 1px solid gray;padding:2px;"><?php echo $agent['Agent_id'];?></div></td>
				<td  width="280"><div  style="border: 1px solid gray;padding:2px;"><?php echo $agent['AgentName'];?></div></td>
				<td  width="200"><div  style="border: 1px solid gray;padding:2px;">Ph: <?php echo $agent['AgentPhone'];?></div></td>
				<td  width="100"><div  style="border: 1px solid gray;padding:2px;"><?php echo $agent['ListAgentId'];?></div></td>
			</tr>
			<tr>
				<td  width="280" rowspan="2" colspan="2"  valign="top"><div  style="border: 1px solid gray;padding:2px;">Email: <?php echo $agent['AgentEmail'];?></div></td>
				<td  width="200" colspan="2"><div  style="border: 1px solid gray;padding:2px;">Cell: <?php echo $agent['AgentCell'];?></div></td>
			</tr>
			<tr>
				<td  width="100" colspan="2"><div  style="border: 1px solid gray;padding:2px;">Fax: <?php echo $agent['AgentFax'];?></div></td>
			</tr>
		</table>
	<?php
	}
	else
	{
		echo 'No data found';
	}
	?>
	</div>
	</div>
</div>