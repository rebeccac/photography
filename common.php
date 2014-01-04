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


function queryDB($query, $bindings, $conn) {
	$stmt = $conn->prepare($query);
	$stmt->setFetchMode(PDO::FETCH_OBJ);
	$stmt->execute($bindings);
	$results = $stmt->fetchAll();

	return $results ? $results : false;
}


?>