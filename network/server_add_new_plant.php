<?php
	include "../lib/System_classes.php";
	header('Content-Type: application/json');
	if (isset($_POST['id']) && isset($_POST['username']))
	{
		echo plant::add_new_plant($_POST['id'],$_POST['username']);
	}
	else return null;
?>