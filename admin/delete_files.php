 <?php
 if(isset($_POST['submit'])) {
 	if (isset($_POST['deleteImages'])) {
 		$deleteImages = $_POST['deleteImages'];
 		 $allowedOrientations = array('landscape', 'portrait'); # only allow query to access $orientation tables in $allowedTables

 		foreach($deleteImages as $imageData) {
 			$imageData = explode(":", $imageData);
 			$filename = $imageData[0];
 			$orientation = $imageData[1];
			if (in_array($orientation, $allowedOrientations)) {
	 			deleteValuesDB("DELETE FROM photo WHERE filename = :filename AND orientation = :orientation",
	 					array('filename' => $filename,
	 						  'orientation' => $orientation), $conn);
	 			deleteImage("../images/frontpage/{$orientation}/{$filename}");
	 		}
 		}
 	}
 	header('location:delete_images.php');
 }
 ?>