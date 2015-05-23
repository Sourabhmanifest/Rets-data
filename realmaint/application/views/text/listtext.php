<?php //echo '<pre>';print_r($text);?>
<div style="border:1px solid gray;margin:5px;">
	<div style="border-bottom:1px solid gray;padding:10px;" id="second-tabs">
		<span id="public-nav" class="selected" onClick="realText('<?php echo $text['Property_id'];?>','PUBLIC');selectNav('public-nav');">PUBLIC</span>
		<span id="feature-nav"onClick="realText('<?php echo $text['Property_id'];?>','FEATURE');selectNav('feature-nav');">FEATURE</span>
		<span id="decode-nav" onClick="realText('<?php echo $text['Property_id'];?>','DECODED');selectNav('decode-nav');">DECODE</span>
		<span  id="agent-nav" onClick="realText('<?php echo $text['Property_id'];?>','AGENT');selectNav('agent-nav');">AGENT</span>
	</div>
	<div id="realtext">
		<?php $this->load->view('text/realtext');?>
	</div>
</div>