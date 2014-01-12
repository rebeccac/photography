<?php 
	require ('../common.php');
	include($root.'includes/dtd.php');?>
	<title>Simon Cordingley Photography - Admin</title>
<?php
	include($root."includes/head.php");
	?>
	<div class="container">
	<?php
	include($root.'includes/nav.php');
	include($root.'includes/header.php');
	include($root.'includes/main-content.php'); 
?>
	<div class="admin-main">
		<div class="admin-main-content">
			<h2 class="admin">Admin Control Panel</h2>
			<span class="admin-buttons">
				<div class="admin-menu-option"><a href="upload_images.php"><img admin-img src="img/upload_image.png" alt="Upload an image to the front page" width="100px" height="100px"></a></div>
				<div class="admin-menu-option"><a href="delete_images.php"><img src="img/delete_images.png" alt="Delete an image from the front page" width="100px" height="100px"></a></div>
				<div class="admin-menu-option"><img src="img/gallery_admin.png" alt="Gallery admin" width="100px" height="100px"></div>
				<div class="admin-menu-option"><img src="img/store_admin.png" alt="Store admin" width="100px" height="100px"></div>
			</span>
		</div>

 <?php
include($root.'includes/footer.php');
 ?>