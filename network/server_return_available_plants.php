<?php
	include "../lib/System_classes.php";
	header('Content-Type: application/json');
	if (isset($_POST['username']))
	{
		echo plant::send_available_plants_to_interface($_POST['username']);
	}
	else return null;
?>