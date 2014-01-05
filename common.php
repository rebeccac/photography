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


/*
Selects a random record from the given table and returns its filename and alt text in an 
array. If the table is empty, returns a default image stored outside the table's photo directory.
*/
function randomImage($orientation, $conn) {
	$allowedTables = array('landscape', 'portrait'); # only allow query to access $orientation tables in $allowedTables
	if (in_array($orientation, $allowedTables)) {

		$result = queryDB("SELECT * FROM {$orientation} ORDER BY RAND()",
							array(),
							$conn)[0];
		$filename = "{$orientation}_filename";
		$alt_text = "{$orientation}_alt_text";
		if ( $result ) {
			$randomImage = array(
				'filename' => "images/frontpage/{$orientation}/{$result->$filename}",
				'alttxt' => $result->$alt_text );
		} else {
			switch ($orientation) {
	    		case "landscape":
	        		$randomImage = array(
						'filename' => "images/frontpage/katariina.jpg",
						'alttxt' => "The Ruination of Katariina" );
	        		break;
	    		case "portrait":
	        		$randomImage = array(
						'filename' => "images/frontpage/ruins_of_katariina.jpg",
						'alttxt' => "The Ruination of Katariina" );
	        	break;
			}
		}	
		return $randomImage;
	}
	else die("Error");
}

function deleteImage($orientation, $filename, $conn) {

	$file = "../images/frontpage/{$orientation}/{$filename}";

	if (file_exists($file)) {
		unlink( $file );
	} else {
		echo "File does not exist";
	}
}


function displayThumbnail($photo, $width) {
	echo '<img src="'.$photo.'" width = "'.$width.'px">';
}

?>