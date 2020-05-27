<?php
	include "../lib/System_classes.php";
	header('Content-Type: application/json');
	if (isset($_POST['id']))
	{
		echo plant::send_properties_to_interface($_POST['id']);
	}
	else return null;
?>