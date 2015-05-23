<div id="content">
	<div style="border:1px solid grey; padding:10px" >
		<form name="find_keyword" onSubmit="findPhrase();return false;">
			<b>Find: </b><input type="text" name="search" id="search_keyword" value="" style="height: 20px;" />
			<span><input class="inputbtn" type="submit" value="GO" required></span>
			<span class="red" id="e_search_keyword"></span>

			<span >
				<input style="margin-left:12px"type="radio" name="qdna" value="" checked/>All
				<input style="margin-left:12px"type="radio" name="qdna" value="f4"/>F4
				<input style="margin-left:12px"type="radio" name="qdna" value="akb"/>AKB
				<input style="margin-left:12px"type="radio" name="qdna" value="swm"/>SWM
				<input style="margin-left:12px"type="radio" name="qdna" value="fluff"/>Fluff
				<input style="margin-left:12px"type="radio" name="qdna" value="uet"/>UET
				<input style="margin-left:12px"type="radio" name="qdna" value="p"/>P
			</span>

			<div style="float:right;margin-top: 5px;">
				<span class="green" id="success_msg"></span>
				<span class="btn" onclick="addEditKeyword('');openPopDiv('popupEdit');" href="javascript:void(0);">Add Keyword</span>
				<!-- <span class="btn" onclick="addEdit('Edit'); openPopDiv('popupEdit');" href="javascript:void(0);">Edit</span>
				<span class="btn">Delete</span> -->
			</div>
		</form>
	</div>

	<div style="border:1px solid grey; padding:10px;margin-top:10px;">
		<table cellspacing="0" cellpadding="0" border="0" class="data-table" width="100%">
			 <thead>
				<tr>
					<th>ID</th>
					<th>QDNA</th>
					<th><input  type="radio" name="find" value="KeyWord" checked>Keyword Phrase</th>
					<th><input style="margin-left:12px" type="radio" name="find" value="Adjective">Adjective</th>
					<th>Seq</th>
					<th>Rel</th>
					<th><input style="margin-left:12px" type="radio" name="find" value="Token">Token</th>
					<th>Wt</th>
					<th>Jump</th>
					<th>&nbsp;&nbsp;Edit&nbsp;&nbsp;|&nbsp;&nbsp;Delete</th>
				</tr>
			 </thead>

			 <tbody id="key-result">
				
			 </tbody>
		</table>
	</div>


</div>

<!--PopUp : Start-->
<div id="popupEdit"></div>
<!--PopUp : End-->