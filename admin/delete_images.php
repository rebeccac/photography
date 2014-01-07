<?php 
	require ('../common.php');
	include($root.'includes/dtd.php');?>
	<title>Simon Cordingley Photography - Admin - Delete a front page photo</title>
<?php
	include($root."includes/head.php");
	include($root.'includes/nav.php');
	include($root.'includes/header.php');
	include($root.'includes/main-content.php'); 

	$conn = connect($config);

	if ( !$conn ) {
		echo "Cannot connect to the database. Please try again later.";
	}
?>
	<div class="admin-main">
		<div class="admin-main-content">

	<h2 class="admin">Delete a front page photo</h2>
	<form action=<?php echo $_SERVER['PHP_SELF']; ?>  method="post">
	<?php displayImages('portrait', $conn);
		  displayImages('landscape', $conn);
	?>
		<br><br>
		<input type="submit" name="submit" value="Delete images">
	</form>
<?php 

 include('delete_files.php');

 ?>
	</div>
<?php include($root.'includes/footer.php'); ?>