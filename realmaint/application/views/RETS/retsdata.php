<div id="content">
	<h2 class="left">Properties</h2>
	<div style="clear:both;">
		<div id="property-search-list" style="float:left;margin:5px ;width: 400px;">
			<div style="border:1px solid gray;padding:5px;margin:5px;">
				<div>
					<form onSubmit="rets_data();return false;">
						Selection #
						<input type="text" name="search" id="search" value=""  class="inputsize"/>
						<input class="inputbtn" type="submit" value="Search"/>
						<div class="red" id="e_selection"></div>
					</form>
				</div>
				<div>
					<input  type="radio" name="searchby" value="1"  checked="true" />
					MLS#
					<input  type="radio" name="searchby" value="2" />
					Matrix_Unique_id
				</div>
			</div>

			<!--no data found div -->
			<div id="morelikelistNA" style="border:1px solid gray;padding:5px;margin:5px;display:none;">
				<div  style="border: 1px solid gray; padding:5px;">
					<Strong>Base Property</Strong>
				</div>
				<div style="border:1px solid #97c0ff;padding:5px;margin:5px;" id="Not_available"></div>
			</div>
			<div id="retsdatalist"></div>
		</div>
		
		<div id="detailresult" class="detailblock">
			<div style="border:1px solid gray;padding:5px;margin:5px;">
				<div  style="border: 1px solid gray; padding:5px;">
					<Strong>RETS Data :  </Strong>
				</div>
				<div style="border:1px solid #97c0ff;padding:5px;margin:5px;" id="Nodata"></div>
			</div>
		</div>
		<div id="detail_result" class="detailblock"></div>
	</div>
</div>
