		
			<?php
			//echo '<pre>'; print_r( $result);exit;
			if(count($results)>0)
			{
				$start = 0;
				$count = $start;
				foreach($results as $row)
				{
					$count++;
			?>
			<tr <?php if($count%2 != 0) echo 'class="blue-bg"';?> id="keyword_<?php echo $row['KeyWord_id'];?>">
				<td><?php echo $row['KeyWord_id'];?></td>
				<td id="qdna_<?php echo $row['KeyWord_id'];?>"><?php echo $row['QDNA'];?></td>
				<td id="keyword_val_<?php echo $row['KeyWord_id'];?>"><?php echo $row['KeyWord'];?></td>
				<td id="adjective_<?php echo $row['KeyWord_id'];?>"><?php echo $row['Adjective'];?></td>
				<td id="seq_<?php echo $row['KeyWord_id'];?>"><?php echo $row['Seq'];?></td>
				<td id="relative_<?php echo $row['KeyWord_id'];?>"><?php echo $row['Relative'];?></td>
				<td id="token_<?php echo $row['KeyWord_id'];?>"><?php echo $row['Token'];?></td>
				<td id="wt_<?php echo $row['KeyWord_id'];?>"><?php echo $row['Weight'];?></td>
				<td id="jumpkey_<?php echo $row['KeyWord_id'];?>"><?php echo $row['JumpKey'];?></td>
				<td>
					<a href="javascript:void(0);" class="pencil" onclick="addEditKeyword('<?php echo $row['KeyWord_id'];?>');openPopDiv('popupEdit');">

					<a href="javascript:void(0);" onClick="deleteKeyword('<?php echo $row['KeyWord_id'];?>');" class="delete"></a>
				</td>
			</tr>
			<?php
				}
			}
			?>

