<?php 
	require ('../common.php'); 
	include($root.'includes/dtd.php'); ?>
	 
		<title>Simon Cordingley Photography</title>
		<?php 
			include($root.'includes/head.php');
		 ?>
			<div class="container">
		
		<?php 
			include($root.'includes/nav.php');
			include($root.'includes/header.php');
			include($root.'includes/main-content.php'); 

			$conn = connect($config);

			if ( !$conn ) {
				echo "Cannot connect to the database. Please try again later.";
			} ?>

				<div class="admin-main">
					<div class="admin-main-content">

						<h2 class="admin">Delete a front page photo</h2>
						<div class="displayImages">
							<form action=<?php echo $_SERVER['PHP_SELF']; ?>  method="post">
								<h3>Portrait</h3>
								<div>	
									<?php 
									displayImages('portrait', $conn); ?>
								</div>
								<br>
								<h3>Landscape</h3>
								<div>	
									<?php
								    displayImages('landscape', $conn); ?>
								</div>
							
								<br><br>
								<input type="submit" name="submit" value="Delete images">
							</form>
						<?php 
						 include('delete_files.php');
						 ?>

						 </div>
				</div>
<?php include($root.'includes/footer.php'); ?>