<?php 
	require '../common.php';
	include($root.'includes/dtd.php'); ?>
	<title>Simon Cordingley Photography - Upload a Front Page Image</title>
<?php
	include($root.'includes/head.php');
	include($root.'includes/nav.php');
	include($root.'includes/header.php');
	
	include($root.'includes/main-content.php'); 

	$conn = connect($config);

	if ($conn) {

	}
	else {
		echo "Could not connect to the database";
	}

	
?>
	<div class="admin-main">
		<div class="admin-main-content">

	<h2 class="admin">Upload an image to the front page</h2>

		<form action=<?php echo $_SERVER['PHP_SELF']; ?>  method="post" enctype="multipart/form-data">
			<label for="file" class="admin-font">Filename: </label>
			<input type="file" name="file" id="file"><br>
			<label for="alttext" class="admin-font">Alt text: </label>
			<input type="text" name="alttext" id="alttext" size="60"><br>
			<input type="submit" name="submit" value="Submit">
		</form>
<?php include('upload_file.php'); ?>
</div>
<?php include($root.'includes/footer.php'); ?>