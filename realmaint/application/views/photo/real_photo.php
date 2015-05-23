
<div class="box border-bottom" >
	<div class="innerbox">
		<Strong>Listing Id: </Strong>
		<span><input class="width140" disabled value=<?php echo $photoData['ListingID']; ?>></input></span>
		<Strong style="margin-left:50px;">Photo Id: </Strong>
		<span><input class="width140" disabled value=<?php echo $captionData['Photo_id'];?>></input></span>
		<Strong style="margin-left:50px;">Image:  <?php echo "<select id='seqno' onchange='imageJump(";
				//echo $captionData['Seqno'];
				//echo ",";
				echo $photoData['ListingID'];
				echo",";
				echo $photoData['PhotoCount'];
				echo")' >";
				for ($jump = 1; $jump <= $photoData['PhotoCount']; $jump++){
					echo "<option value='$jump'>$jump</option>";}
					echo "</select>";
				?> of <?php echo $photoData['PhotoCount']; ?></Strong>
	</div>
	
	<div class="innerbox">
		<Strong>Folder: </Strong>
		<?php
			// remove '/'from front of the folder name
			$folder=(end(explode("/",$photoData['Folder'])));
		?>
		<span><input class="f21" disabled value=<?php echo $folder; ?>></input></span>
		<span><input onclick="imagePrev(<?php echo $photoData['ListingID'];?>,<?php echo $photoData['PhotoCount'];?>)" class="inputbtn margin31" type="submit" value="Prev"/></span>
		<span><input onclick="imageNext(<?php echo $photoData['ListingID'];?>,<?php echo $photoData['PhotoCount'];?>)" class="inputbtn" type="submit" value="Next"/></span>
	</div>
	<div class="innerbox height144">
		<span class="newlabel"><strong>Caption:</strong></span>
		<span><textarea class="textarea3" disabled ><?php echo $captionData['Caption']; ?></textarea></span><br>
		<span class="newlabel"><Strong>Tagline: </Strong></span>

		<?php if($tagline['Comment']=='no')
			{?>
		<span><textarea id='tag' class="textarea2"placeholder='Your comment please!'></textarea></span>
		<?php }
		else{?>
		<span><textarea id='tag' class="textarea2"><?php echo $tagline['Comment']; ?></textarea></span>
		<?php
		}?>

		<span id='msg' class="imgalert"></span>

			<input class="inputbtn btn-left right" type="submit" value="Save" onClick="tagline(<?php echo $captionData['Photo_id'];?>)"/>

	</div>
	
</div>

<div class="imagebox">

<?php $imagename=$photoData["ListingID"].'_'.$captionData['Seqno'].'.jpg';?>
<img class="image" src="<?PHP echo IMAGE_PATH; echo $folder;?>/<?php echo $imagename;?>" alt="No Image"></img>
<div class="lb-nav image"  style="display: block;">
<a class="lb-prev" onclick="imagePrev(<?php echo $photoData['ListingID'];?>,<?php echo $photoData['PhotoCount'];?>)"></a>
<a class="lb-next" onclick="imageNext(<?php echo $photoData['ListingID'];?>,<?php echo $photoData['PhotoCount'];?>)" ></a>
</div>
</div>