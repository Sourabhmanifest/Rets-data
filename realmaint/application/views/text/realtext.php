<?php
if($text)
{
?>
<div style="border-bottom:1px solid gray;padding:5px;">
	<Strong>Real Text</Strong>
	<span style="border: 1px solid gray;padding:2px;"><?php echo $text['TextSource'];?></span>
	<span  style="border: 1px solid gray;padding:2px;"><?php echo $text['Text_id'];?></span>
</div>
<!-- <div style="padding:5px;overflow: scroll;height:200px;"><?php echo $text['PublicRemarks'];?></div>-->
<div style="padding:5px;overflow: scroll;height:200px;"><?php echo $text['Comments'];?></div>


<?php
}
else
{
	echo '<b>&nbsp;&nbsp;&nbsp;&nbsp;No data found</b>';
}
?>
