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

	$allowedExts = array("jpeg", "jpg", "png");
	$temp = explode(".", $_FILES["file"]["name"]);
	$extension = end($temp);
	if ((($_FILES["file"]["type"] == "image/jpeg")
	|| ($_FILES["file"]["type"] == "image/jpg")
	|| ($_FILES["file"]["type"] == "image/pjpeg")
	|| ($_FILES["file"]["type"] == "image/x-png")
	|| ($_FILES["file"]["type"] == "image/png"))
	&& ($_FILES["file"]["size"] < 1000000)
	&& in_array($extension, $allowedExts))
	  {
	  if ($_FILES["file"]["error"] > 0)
	    {
	    echo "Return Code: " . $_FILES["file"]["error"] . "<br>";
	    }
	  else
	    {
	    echo "Upload: " . $_FILES["file"]["name"] . "<br>";
	    echo "Type: " . $_FILES["file"]["type"] . "<br>";
	    echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
	    // echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br>";

	    if (file_exists("../images/frontpage/" . $_FILES["file"]["name"]))
	      {
	      	$photo = "../images/frontpage/".$_FILES["file"]["name"];
	      	echo $_FILES["file"]["name"] . " already exists. ";
	      }
	    else
	      {
	      move_uploaded_file($_FILES["file"]["tmp_name"],
	      "../images/frontpage/" . $_FILES["file"]["name"]);
		  
		  $photo = "../images/frontpage/".$_FILES["file"]["name"];
	      
	      displayThumbnail($photo);

	      // echo "Stored in: " . "images/frontpage/" . $_FILES["file"]["name"];

	      ?>
	      <div>
				<p><span class="emphasis">Stored in: </span><span><?= "images/frontpage/" . $_FILES["file"]["name"]; ?></span></p>
	      </div>
	      <?php

	      }
	    }
	  }
	else
	  {
	  echo "Invalid file";
	  }



	function displayThumbnail($photo) {
		echo '<img src="'.$photo.'" width = "200px">';
		echo "<br><br>";

	}

include('includes/footer.php');
 ?>
