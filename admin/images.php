<?php 
	include('../config.php');
	include($root.'includes/dtd.php');?>
	<title>Simon Cordingley Photography - Upload a Front Page Image</title>
<?php
	include($root.'includes/head.php');
	include($root.'includes/nav.php');
	include($root.'includes/header.php');
	
	include($root.'includes/main-content.php'); 
?>
	<div class="admin-main">

<?php

if (isset($_POST['submit'])) {

	function displayThumbnail($photo) {
		echo '<img src="'.$photo.'" width = "200px">';
		echo "<br><br>";

	}



	$allowedExts = array("jpeg", "jpg", "png");
	$temp = explode(".", $_FILES["file"]["name"]);
	$extension = end($temp);
	if ((($_FILES["file"]["type"] == "image/jpeg")
	|| ($_FILES["file"]["type"] == "image/jpg")
	|| ($_FILES["file"]["type"] == "image/pjpeg")
	|| ($_FILES["file"]["type"] == "image/x-png")
	|| ($_FILES["file"]["type"] == "image/png"))
	&& ($_FILES["file"]["size"] < 1000000)
	&& in_array($extension, $allowedExts))
	  {
	  if ($_FILES["file"]["error"] > 0)
	    {
	    echo "Return Code: " . $_FILES["file"]["error"] . "<br>";
	    }
	  else
	    {
	    echo "Upload: " . $_FILES["file"]["name"] . "<br>";
	    echo "Type: " . $_FILES["file"]["type"] . "<br>";
	    echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";

	    if (file_exists("../images/frontpage/" . $_FILES["file"]["name"]))
	      {
	      	$photo = "../images/frontpage/".$_FILES["file"]["name"];
	      	echo $_FILES["file"]["name"] . " already exists. ";
	      }
	    else
	      {
	      move_uploaded_file($_FILES["file"]["tmp_name"],
	      "../images/frontpage/" . $_FILES["file"]["name"]);
		  
		  $photo = "../images/frontpage/".$_FILES["file"]["name"];
	      
	      displayThumbnail($photo);

	      ?>
	      <div>
				<p class="admin-font"><span class="emphasis">Stored in: </span><span><?= "images/frontpage/" . $_FILES["file"]["name"]; ?></span></p>
	      </div>
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

	<h2 class="admin">Upload an image to the front page</h2>
		<form action=<?php echo $_SERVER['PHP_SELF']; ?>  method="post" enctype="multipart/form-data">
			<label for="file" class="admin-font">Filename: </label>
			<input type="file" name="file" id="file"><br>
			<label for="alttext" class="admin-font">Alt text: </label>
			<input type="text" name="alttext" id="alttext" size="60"><br>
			<input type="submit" name="submit" value="Submit">
		</form>
	

 <?php
include($root.'includes/footer.php');
 ?>