<?php 
require 'common.php';
include($root.'includes/dtd.php'); 
 ?>
	<title>Simon Cordingley Photography</title>
		<?php include($root.'includes/head.php');
		 ?>
			<!-- <div class="container"> -->
		<?php include($root.'includes/nav.php');
			  include($root.'includes/header.php');
			  include($root.'includes/main-content.php'); 

			  $conn = connect($config);

			  $landscape = randomImage('landscape', $conn);
			  $portrait = randomImage('portrait', $conn);
		?>

				<div class="landscape-main-content">
					<div class="centred">
						<img src="<?php echo $landscape['filename']; ?>" alt="<?php echo $landscape['alttext']; ?> ">
					</div>
				</div>
				
				<div class="portrait-main-content">
						<img src="<?php echo $portrait['filename']; ?>" alt="<?php echo $portrait['alttext']; ?> ">
				</div>

			<!--</div> --><!-- main-content -->

		<!-- <div class="push"></div>
		</div> -->
		<?php include($root.'includes/footer-fixed.php'); ?>
	