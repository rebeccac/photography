 <?php
 if(isset($_POST['submit'])) {
 	if (isset($_POST['deleteImages'])) {
 		$deleteImages = $_POST['deleteImages'];
 		 $allowedTables = array('landscape', 'portrait'); # only allow query to access $orientation tables in $allowedTables

 		foreach($deleteImages as $imageData) {
 			$imageData = explode(":", $imageData);
 			
			if (in_array($imageData[1], $allowedTables)) {
				$column = "{$imageData[1]}_filename";
	 			deleteValuesDB("DELETE FROM {$imageData[1]} WHERE {$column} = :filename",
	 					array('filename' => $imageData[0]), $conn);
	 			deleteImage($imageData[1], $imageData[0], $conn);
	 		}
 		}
 	}
 	header('location:delete_images.php');
 }
 ?>