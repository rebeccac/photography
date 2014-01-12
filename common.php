<?php
 require '/Applications/XAMPP/xamppfiles/htdocs/config/config.php'; # development path

// require($_SERVER[DOCUMENT_ROOT]."/../config.php"); # production path

/* Connect to database */
function connect($config) {
	try {
		$conn = new PDO("mysql:host=localhost;dbname={$config['DB_NAME']}",
						$config['DB_USERNAME'],
						$config['DB_PASSWORD']);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $conn;
	} 
	catch(Exception $e) {
		return false;
	}
}

/* 
Returns all rows from $tableName and checks that table is not empty. 
If $tableName does not exist or is empty, returns false.
*/
function getAllRows($tableName, $conn) {
	try {
		$result = $conn->query("SELECT * FROM $tableName");
		return $result->rowCount() > 0 ? $result : false;
	}
	catch(Exception $e) {
		return false;
	}
}

/*
Dynamically query database. 
$query: MySQL query
$bindings: values to bind to query
*/
function queryDB($query, $bindings, $conn) {
	$stmt = $conn->prepare($query);
	$stmt->setFetchMode(PDO::FETCH_OBJ);
	$stmt->execute($bindings);
	$results = $stmt->fetchAll();
	return $results ? $results : false;
}


/*
Insert values into database. 
$query: MySQL query
$bindings: values to bind to query
*/
function insertValuesDB($query, $bindings, $conn) {
	$stmt = $conn->prepare($query);
	$stmt->setFetchMode(PDO::FETCH_OBJ);
	$stmt->execute($bindings);
}

function deleteValuesDB($query, $bindings, $conn) {
	$stmt = $conn->prepare($query);
	$stmt->setFetchMode(PDO::FETCH_OBJ);
	$stmt->execute($bindings);
}


/*
Selects a random record from the given table and returns its filename and alt text in an 
array. If the table is empty, returns a default image stored outside the table's photo directory.
*/
function randomImage($orientation, $conn) {
	$allowedOrientations = array('landscape', 'portrait'); # only allow query to access $orientation tables in $allowedTables
	if (in_array($orientation, $allowedOrientations)) {
		$result = queryDB("SELECT * FROM photo WHERE orientation = :orientation ORDER BY RAND()",
							array('orientation' => $orientation),
							$conn)[0];
		if ( $result ) {
			$randomImage = array(
				'filename' => "images/frontpage/{$orientation}/{$result->filename}",
				'alttext' => $result->alt_text );
		} else {
			switch ($orientation) {
	    		case "landscape":
	        		$randomImage = array(
						'filename' => "images/frontpage/katariina.jpg",
						'alttext' => "The Ruination of Katariina" );
	        		break;
	    		case "portrait":
	        		$randomImage = array(
						'filename' => "images/frontpage/ruins_of_katariina.jpg",
						'alttext' => "The Ruination of Katariina" );
	        	break;
			}
		}	
		return $randomImage;
	}
	else die("Error");
}


/* Display images in the database on delete_images page */
function displayImages($orientation, $conn) {
	$allowedOrientations = array('landscape', 'portrait'); # only allow query to access $orientation tables in $allowedTables
	if (in_array($orientation, $allowedOrientations)) {
		$images = queryDB("SELECT * FROM photo WHERE orientation = :orientation",
							array('orientation' => $orientation), $conn);
		if ( $images ) { 
			foreach ( $images as $image ) {  ?>	
				<div class="thumbnail">
					<img src="<?= "../images/frontpage/{$orientation}/{$image->filename}" ?>" width="200px" alt="<?= $image->alt_text?>">
					<br>				
 					<input type="checkbox" name="deleteImages[]" value="<?php echo $image->filename.":".$orientation ?>"><p class="admin">Delete photo</p>
 				</div> 
			<?php	
	    	}
		} else { ?>
			<div class="emptyDirectory">No <?php echo $orientation; ?> images available. The default image will be used for <?php echo $orientation; ?> orientation.</div>
		<?php
		}
	}
}


/* Delete file from directory */
function deleteImage($path, $conn) {
	if (file_exists($path)) {
		unlink( $path );
	} else {
		echo "File does not exist";
	}
}


function displayThumbnail($photo, $width) {
	echo '<img src="'.$photo.'" width = "'.$width.'px">';
}

?>