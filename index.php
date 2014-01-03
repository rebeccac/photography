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
?>
	
	<div class="landscape-main-content">
		<div class="centred">
			<img src="images/frontpage/katariina.jpg" alt="The Ruination of Fort Katariina">
		</div>

	</div>
	
	<div class="portrait-main-content">
			<img src="images/frontpage/ruins_of_katariina.jpg" alt="The Ruins of Katariina">
	</div>

	
 


 <?php
include($root.'includes/footer.php');
 ?>