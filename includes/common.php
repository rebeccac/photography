<?php
require '../../config.php';

/* Connect to database */
function connect($config) {
	try {
		$conn = new PDO('mysql:host=localhost;dbname=images',
						$config['DB_USERNAME'],
						$config['DB_PASSWORD']);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $conn;
	} 
	catch(Exception $e) {
		return false;
	}
}



?>