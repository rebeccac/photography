<?php 
	include('../config.php');
	include($root.'includes/dtd.php');?>
	<title>Simon Cordingley Photography - Upload a Front Page Image</title>
<?php
	include($root.'includes/head.php');
	include($root.'includes/nav.php');
	include($root.'includes/header.php');
	
	include($root.'includes/main.php'); 
?>
	
		<form action="upload_file.php" method="post" enctype="multipart/form-data">
			<label for="file">Filename: </label>
			<input type="file" name="file" id="file"><br>
			<label for="alttext">Alt text: </label>
			<input type="text" name="alttext" id="alttext"><br>
			<input type="submit" name="submit" value="Submit">
		</form>

 <?php
include($root.'includes/footer.php');
 ?>