<?php
 require '/Applications/XAMPP/xamppfiles/htdocs/config/config.php'; # development path

# require($_SERVER[DOCUMENT_ROOT]."/../config.php"); # production path

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
Selects a random record from the landscape table and returns its filename and alt text in an 
array. If the table is empty, returns a default image stored outside the landscape photo directory.
*/
function randomLandscapeImage($conn) {
	$result = queryDB("SELECT * FROM landscape ORDER BY RAND()",
						array(),
						$conn)[0];
	if ( $result ) {
		$landscapeImage = array(
			'filename' => "images/frontpage/landscape/" . $result->landscape_filename,
			'alttxt' => $result->landscape_alt_text );
	} else {
		$landscapeImage = array(
			'filename' => "images/frontpage/katariina.jpg",
			'alttxt' => "The Ruination of Katariina" );
	}
	return $landscapeImage;
}

/*
Selects a random record from the portrait table and returns its filename and alt text in an 
array. If the table is empty, returns a default image stored outside the portrait photo directory.
*/
function randomPortraitImage($conn) {
	$result = queryDB("SELECT * FROM portrait ORDER BY RAND()",
						array(),
						$conn)[0];
	if ( $result ) {
		$portraitImage = array(
			'filename' => "images/frontpage/portrait/" . $result->portrait_filename,
			'alttxt' => $result->portrait_alt_text );
	} else {
		$portraitImage = array(
			'filename' => "images/frontpage/ruins_of_katariina.jpg",
			'alttxt' => "The Ruination of Katariina" );
	}
	return $portraitImage;
}


function displayThumbnail($photo, $width) {
	echo '<img src="'.$photo.'" width = "'.$width.'px">';
}

?>