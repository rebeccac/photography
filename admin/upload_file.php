<?php
if (isset($_POST['submit'])) {

	$alttext = isset($_POST['alttext']) ? $_POST['alttext'] : '';

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
	    	$filename = $_FILES["file"]["name"];
	    	$kb = number_format(($_FILES["file"]["size"] / 1024), 2, '.', ',');

	    	if ($width > $height) {
	    	$orientation = 'landscape';
		    }
		    else {
		    	$orientation = 'portrait';	
		    }
		    $photo = "../images/frontpage/{$orientation}/{$filename}";


		  	if (file_exists($photo)) {
		      	echo $filename . " already exists. ";
		    } else {
		      	move_uploaded_file($temp, $photo);
		      	if ( $conn ) {
		      		insertValuesDB("INSERT INTO photo(orientation, filename, alt_text) VALUES (:orientation, :filename, :alt_text)",
		      						array('orientation' => $orientation,
		      							  'filename' => $filename,
		      							  'alt_text' => $alttext),
		      						$conn);
		      	}
		      	else {
		      		echo "Cannot connect to the database. Please try again later.";
		      	}
	      	?>
				<h3 class="admin">File successfully uploaded</h3>
				<p class="admin-font">
				    <span class="bold">Upload: </span><span><?= $filename; ?></span>
				    <br>
					<span class="bold">Type: </span><span><?= $_FILES["file"]["type"] ?></span>
					<br>	    
					<span class="bold">Size: </span><span><?= $kb . "kB" ?></span>
		 			<br>
				    <span class="bold">Width: </span><span><?= $width . "px" ?></span>
				    <br>
				    <span class="bold">Height: </span><span><?= $height . "px" ?></span>
				    <br>
				    <span class="bold">Stored in: </span><span><?= $photo; ?></span>
				    <br>
				    <span class="bold">Alt text: </span><span><?= $alttext; ?></span>
				    <br>		
				    <br>
				</p>
	      	<?php
		        displayThumbnail($photo, 200);
		        echo "<br><br>";
			}
		}
	} else {
	  	echo "Invalid file";
	  }
}
?>