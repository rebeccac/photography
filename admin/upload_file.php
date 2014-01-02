<?php

if (isset($_POST['submit'])) {

	function displayThumbnail($photo) {
		echo '<img src="'.$photo.'" width = "200px">';
		echo "<br><br>";

	}

	$allowedExts = array("jpeg", "jpg", "png", "JPG", "JPEG", "PNG");
	$temp = explode(".", $_FILES["file"]["name"]);
	$extension = end($temp);
	if ((($_FILES["file"]["type"] == "image/jpeg")
	|| ($_FILES["file"]["type"] == "image/jpg")
	|| ($_FILES["file"]["type"] == "image/pjpeg")
	|| ($_FILES["file"]["type"] == "image/x-png")
	|| ($_FILES["file"]["type"] == "image/png"))
	&& ($_FILES["file"]["size"] < 3000000)
	&& in_array($extension, $allowedExts))
	  {
	  if ($_FILES["file"]["error"] > 0)
	    {
	    echo "Return Code: " . $_FILES["file"]["error"] . "<br>";
	    }
	  else
	    {
	    	$temp = $_FILES["file"]["tmp_name"];
	    	$size = getimagesize($temp);
	    	$width = $size[0];
	    	$height = $size[1];
	    	$kb = number_format(($_FILES["file"]["size"] / 1024), 2, '.', ',');

	    	if ($width > $height) {
	    	$directory = 'landscape/';
		    }
		    else {
		    	$directory = 'portrait/';
		    }
	?>
		<h3 class="admin">File successfully uploaded</h3>
		<p class="admin-font">
		    <span class="bold">Upload: </span><span><?= $_FILES["file"]["name"] ?></span>
		    <br>
			<span class="bold">Type: </span><span><?= $_FILES["file"]["type"] ?></span>
			<br>	    
			<span class="bold">Size: </span><span><?= $kb . "kB" ?></span>
 			<br>
		    <span class="bold">Width: </span><span><?= $width . "px" ?></span>
		    <br>
		    <span class="bold">Height: </span><span><?= $height . "px" ?></span>
		    <br>
		    <span class="bold">Stored in: </span><span><?= "images/frontpage/" .$directory . $_FILES["file"]["name"]; ?></span>
		    <br>
		    <br>		
		</p>
<?php

		$photo = "../images/frontpage/" . $directory . $_FILES["file"]["name"];
	    
	    if (file_exists("../images/frontpage/" . $directory . $_FILES["file"]["name"]))
	      {
	      	echo $_FILES["file"]["name"] . " already exists. ";
	      }
	    else
	      {

	        move_uploaded_file($_FILES["file"]["tmp_name"], "../images/frontpage/" . $directory . $_FILES["file"]["name"]);
	      
	        displayThumbnail($photo);
	      ?>
	        <!-- <div>
				<p class="admin-font"><span class="bold">Stored in: </span><span><?= "images/frontpage/" .$directory . $_FILES["file"]["name"]; ?></span></p>
	        </div> -->
	        <?php
		}
	}
}
	else
	  {
	  	echo "Invalid file";
	  }
}
?>