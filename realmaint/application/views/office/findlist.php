<div style="border:1px solid gray;margin:5px;">
	<table border="0" cellspacing="0" cellpadding="0" class="data-table">
		<TR	>
			<th> </th>
			<th  width="50">Id</th>
			<th  width="280">Name</th>
			<th  width="200">Phone</th>
			<th  width="100">Rec_id</th>
		</TR>
		<?php 
		if($list) 
		foreach($list as $list_row)
		{
		?>
		<TR	>
			<TD height="30"><input  type="radio" name="result"/></TD>
			<Td  width="50"><div  style="border: 1px solid gray;padding:2px;"><?php echo $list_row[$search_type.'_id'];?></div></Td>
			<Td  width="280"><div  style="border: 1px solid gray;padding:2px;"><?php echo $list_row[$search_type.'Name'];?></div></Td>
			<Td  width="200"><div  style="border: 1px solid gray;padding:2px;"><?php echo $list_row[$search_type.'Phone'];?></div></Td>
			<Td  width="100"><div  style="border: 1px solid gray;padding:2px;"><?php echo $list_row['List'.$search_type.'Id'];?></div></Td>
		</TR>
		<?php
		}
		else
		{
			echo '<tr><td colspan="4">No data found</td></tr>';
		}
		?>
	</TABLE>
</div>