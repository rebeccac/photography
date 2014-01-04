<?php 
	require ('common.php');
	include($root.'includes/dtd.php');?>
	<title>Simon Cordingley Photography</title>
<?php
	include($root.'includes/head.php');
	include($root.'includes/nav.php');
	include($root.'includes/header.php');
	
	include($root.'includes/main-content.php'); 

	$conn = connect($config);

	$landscapeImage = randomLandscapeImage($conn);
	$portraitImage = randomPortraitImage($conn);
?>
	<div class="landscape-main-content">

		<div class="centred">
			<img src="<?php echo $landscapeImage['filename']; ?>" alt="<?php echo $landscapeImage['alttxt']; ?> ">
		</div>

	</div>
	
	<div class="portrait-main-content">
			<img src="<?php echo $portraitImage['filename']; ?>" alt="<?php echo $portraitImage['alttxt']; ?> ">
	</div>

	
 


 <?php
include($root.'includes/footer.php');
 ?>