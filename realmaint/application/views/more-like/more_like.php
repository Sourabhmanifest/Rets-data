<div id="content">
	<h2 class="left">Properties</h2>
	<div style="clear:both;">
		<div id="property-search-list" style="float:left;margin:5px ;width: 388px;">
			<div style="border:1px solid gray;padding:5px;margin:5px;">
				<div>
					<form onSubmit="more_like_list();return false;">
						Selection #
						<input type="text" name="search" id="search" value="" class="inputsize"/>
						<input class="inputbtn" type="submit" value="Search"/>
						<div class="red" id="e_selection"></div>
					</form>
				</div>
				<div>
					<input  type="radio" name="searchby" value="1" checked="true" />
					PropertyID
					<input  type="radio" name="searchby" value="2" />
					MLS#
					<input  type="radio" name="searchby" value="3" />
					ListingId#
				</div>
			</div>
			<!--no data found div -->
			<div id="morelikelistNA" style="border:1px solid gray;padding:5px;margin:5px;display:none;">
				<div  style="border: 1px solid gray; padding:5px;">
					<Strong>Base Property</Strong>
				</div>
				<div style="border:1px solid #97c0ff;padding:5px;margin:5px;" id="Not_available"></div>
			</div>
			<!--data foud div-->
			<div id="more_like_list"></div>
		</div>
		<!--no data found div -->
		<!-- <div id="detailinfo" style="float:left;margin:5px; width: 690px;display:none;">
			<div style="border:1px solid gray;padding:5px;margin:5px;">
				<div  style="border: 1px solid gray; padding:5px;">
					<Strong>More Like Listing</Strong>
				</div>
				<div style="border:1px solid #97c0ff;padding:5px;margin:5px;" id="Notavailable"></div>
			</div>
		</div> -->
		<div id="detailresult" class="detailblock">
			<div style="border:1px solid gray;padding:5px;margin:5px;">
				<div  style="border: 1px solid gray; padding:5px;">
					<Strong>More Like Listing :  </Strong>
				</div>
				<div style="border:1px solid #97c0ff;padding:5px;margin:5px;" id="Nodata"></div>
			</div>
		</div>
		<!--data foud div-->
		<!-- <div id="detail_info" style="float:left;margin:5px; width: 690px;display:none;"></div> -->
		<div id="detail_result" class="detailblock"></div>
	</div>
</div>
