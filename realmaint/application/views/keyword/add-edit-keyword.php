<?php
if(isset($keyWord))
{ 
	 $KeyWord=$keyWord['KeyWord'];
	 $QDNA=$keyWord['QDNA'];
	 $Seq=$keyWord['Seq'];
	 $Adjective=$keyWord['Adjective'];
	 $Relative=$keyWord['Relative'];
	 $Token=$keyWord['Token'];
	 $Weight=$keyWord['Weight'];
	 $JumpKey=$keyWord['JumpKey'];
}
else
{
	 $KeyWord="";
	 $QDNA="";
	 $Seq="";
	 $Adjective="";
	 $Relative="";
	 $Token="";
	 $Weight="";
	 $JumpKey=false;
}  
?>

<h2><?php if($KeyWord_id) echo 'Edit';else echo 'Add';?> Keyword Phrase <?php if($KeyWord_id) echo "<span style='float:right;'>Keyword Id:$KeyWord_id</span>";?></h2>

<?php if(!$KeyWord_id)
{
	$KeyWord_id='0';
}
?>

<div class="content">
	<?php echo form_open('listing/saveKeyword/'.$KeyWord_id, 'name="form_add_edit_keyword"  onSubmit="return saveKeyword('.$KeyWord_id.');"');?>
		<div style="float: left;margin-bottom: 15px;width:100%">
		
			<table class="content-table" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<td width="75">QDNA:<span class="red">*</span></td>
					<td>
						<?php
						$qdna_options['--Select--'] =  '--Select--';
						foreach($qdna_list as $qdna_row)
						{ 
							$qdna_options[$qdna_row['QDNA']] =  $qdna_row['QDNA'];
						}

						echo form_dropdown('qdna', $qdna_options, set_value('QDNA', $QDNA), 'class="input-shadow" id="qdna"');
						?>
						<div class="red" id="e_qdna"></div>
					</td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td width="75">Seq:</td>
					<td>
						<input class='keyinputwidth' type="text" name="seq" value="<?php echo $Seq;?>" id="seq">
						<div class="red" id="e_seq"></div>
					</td>
					
				</tr>
				<tr>
					<td>KeyWord:<span class="red">*</span></td>
					<td>
						<input class='keyinputwidth' type="text" name="keyword" value="<?php echo $KeyWord;?>" id="keyword">
						<div class="red" id="e_keyword"></div>
					</td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td>Rel:</td>
					<td>
						<input class='keyinputwidth' type="text" name="relative" value="<?php echo $Relative;?>" id="relative">
						<div class="red" id="e_rel"></div>
					</td>
					
				</tr>
				<tr>
					<td>Adjective:</td>
					<td>
						<input class='keyinputwidth' type="text" name="adjective" id="adjective" value="<?php echo $Adjective;?>">
						<div class="red" id="e_adj"></div>
					</td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
					<td>Wt:</td>
					<td>
						<input class='keyinputwidth' type="text" name="wt" value="<?php echo $Weight;?>" id="wt">
						<div class="red" id="e_wt"></div>
					</td>
				</tr>
				
				<tr>
					<td>Jump:</td>
					<td>
						<table border="0" cellspacing="0" cellpadding="0" class="nopadtable">
						  <tr>
							<td width="18">
								<input name="jumpkey" type="radio" value="1" class="mar0" <?php echo set_radio('jumpkey', '1', TRUE); ?>/>
							</td>
							<td width="40" valign="middle" class="black font11">Yes</td>
							<td width="18">
								<input name="jumpkey" type="radio" value="0" class="mar0" <?php echo set_radio('jumpkey', '0', !$JumpKey); ?>/>
							</td>
							<td valign="middle" class="black font11">No</td>
						  </tr>
						</table>
						<div class="red" id="e_jump"></div>						
					</td>
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
					<td>Token:<span class="red">*</span></td>
					<td>
						<input class='keyinputwidth' type="text" name="token" value="<?php echo $Token;?>" id="token">
						<div class="red" id="e_token"></div>
					</td>
					<input type="hidden" id="keyword_val"  name="keyword_val" value=""/>
					<input type="hidden" id="radio_val"  name="radio_val" value=""/>
				</tr>
			</table>
			
		</div>
		<div class="popupbtn" style="clear:both;">
			<table border="0" cellspacing="20" cellpadding="0">
				<tr>
					<td>
						<input type="submit"  id="save"  name="save" class="savebtn" value="Save">
					</td>
					<td><span class="btn" href="javascript:void(0);" onclick="closePopDiv('popupEdit');">Cancel</span></td>
				</tr>
			</table>
		</div>
	</form>
</div>



