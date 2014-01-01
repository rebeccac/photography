<?php 
	include('../includes/dtd.php');?>
	<title>Simon Cordingley Photography - Upload a Front Page Image</title>
	<link rel="stylesheet" href="styles/admin_style.css">

		<link href='http://fonts.googleapis.com/css?family=Julius+Sans+One|Quicksand:300,400|Cinzel:400,700,900|Open+Sans:400,600' rel='stylesheet' type='text/css'>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>

	</head>
	<body>
<?php
	include('../includes/nav.php');
	include('../includes/header.php');
	
	include('../includes/main.php'); 
?>
	
		<form action="upload_file.php" method="post" enctype="multipart/form-data">
			<label for="file">Filename:</label>
			<input type="file" name="file" id="file"><br>
			<input type="submit" name="submit" value="Submit">
		</form>

 <?php
include('includes/footer.php');
 ?>