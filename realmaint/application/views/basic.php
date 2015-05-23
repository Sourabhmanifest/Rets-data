<div id="content">
	
	<h2 class="left">Properties</h2>
	<div style="clear:both;">
		<div id="property-search-list" style="float:left;margin:5px ;width: 400px;">
			<div style="border:1px solid gray;padding:5px;margin:5px;">
				<div>
					<form onSubmit="searchProperty();return false;">
					Selection #
					<input type="text" name="search" id="search" value="" />
					<input class="inputbtn" type="submit" value="GO"/>
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
			<div id="property-list"></div>
		</div>
		<div id="property-detail" style="float:left;margin:5px; width: 690px;display:none;">
			<div style="border:1px solid gray;padding:10px;margin:5px;" id="main-tabs">
				<span onClick="listText();selectNav('text-nav');"  class="selected" id="text-nav">TEXT</span>
				<span onClick="listLocation();selectNav('location-nav');" id="location-nav">LOCATION</span>
				<span onClick="listOffice();selectNav('office-nav');" id="office-nav">OFFICE</span>
				<span onClick="realPhoto();selectNav('photo-nav');" id="photo-nav">PHOTO</span>
				<span onClick="selectNav('poly-nav');" id="poly-nav">POLYGON</span>
				<span onClick="selectNav('source-nav');" id="source-nav">SOURCE</span>
			</div>
			
			<div id="detail-info" ></div>
		</div>
	</div>
</div>