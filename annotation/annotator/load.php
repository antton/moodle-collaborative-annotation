 <?php

/**
 * Loads the annotations from the server for the image currently being viewed.
 * GET request with the window.location.url
 */
if(!empty($_POST['url'])) {
	require_once(__DIR__ . "../../../../config.php");
	require_login();

	global $CFG, $DB, $USER;

	$userid = $USER->id; //Gets the current users id

	$table = 'annotation_image';
	$url = $_POST['url'];

	$sql = "SELECT * FROM mdl_annotation_annotation WHERE url = ?";
	$rs = $DB->get_recordset_sql($sql, array($url));
	
	$annotations = array();

	//Loop through results
	foreach($rs as $record) {
		//Get username of annotation creator
		$user = $DB->get_record('user', array("id" =>$record->userid));
		$record->username = $user->firstname . " " . $user->lastname;
		unset($record->userid); //Don't send the user's id, not required
		

		//TODO: Disable editing if the current user didn't create the annotation

		$annotations[] = $record;
	}
	$rs->close();

	echo json_encode($annotations);
}
