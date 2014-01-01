<?php 
	include('../config.php');

	include($root.'includes/dtd.php');?>
	<title>Simon Cordingley Photography - Admin</title>
	<!-- <link rel="stylesheet" href="styles/admin_style.css">

		<link href='http://fonts.googleapis.com/css?family=Julius+Sans+One|Quicksand:300,400|Cinzel:400,700,900|Open+Sans:400,600' rel='stylesheet' type='text/css'>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>

	</head>
	<body> -->
<?php
	include($root."includes/head.php");
	include($root.'includes/nav.php');
	include($root.'includes/header.php');
	
	include($root.'includes/main.php'); 
	echo $root;
?>
	
	<a href="images.php">Upload new front page photos</a>

 <?php
include($root.'includes/footer.php');
 ?>