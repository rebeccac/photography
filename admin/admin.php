<?php 
	include('../rootconfig.php');
	include('../../config.php');

	include($root.'includes/dtd.php');?>
	<title>Simon Cordingley Photography - Admin</title>
<?php
	include($root."includes/head.php");
	include($root.'includes/nav.php');
	include($root.'includes/header.php');
	
	include($root.'includes/main-content.php'); 
?>
	<div class="admin-main">
		<div class="admin-main-content">
			<h2 class="admin">Admin Control Panel</h2>
			<span class="admin-buttons">
				<div class="admin-menu-option"><a href="images.php"><img src="img/upload_image.png"></a></div>
				<div class="admin-menu-option"><img src="img/delete_images.png"></div>
				<div class="admin-menu-option"><img src="img/gallery_admin.png"></div>
				<div class="admin-menu-option"><img src="img/store_admin.png"></div>
			</span>
		</div>

 <?php
include($root.'includes/footer.php');
 ?>