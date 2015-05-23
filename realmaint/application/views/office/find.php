<div style="border:1px solid gray;margin:5px;">
	<div  style="margin:10px;">
		<form action="#"  onSubmit="findOfficeOrAgent(); return false;">
			<Strong>Find:</Strong>
			<input name="search-keyword" id="search-keyword" style="border: 1px solid gray;padding:2px;width:20px;width:100px;"></input>
			<span>
				<input  type="radio" name="search_type" value="Office" checked="checked"/>Office
				<input  type="radio" name="search_type" value="Agent"/>Agent
			</span>
			|
			<span>
				<input  type="radio" name="search_by" value="name" checked="checked"/>Name
				<input  type="radio" name="search_by" value="id"/>Id
			</span>
		</form>
	</div>
</div>

<div id="search-info"></div>