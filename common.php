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
		echo "Connected to DB";
		return $conn;
	} 
	catch(Exception $e) {
		echo "Not connected to DB";
		return false;
	}
}



?>