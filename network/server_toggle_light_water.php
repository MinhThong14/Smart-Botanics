<?php
	include "../lib/System_classes.php";
	
	$id=$_POST['id'];
	$light=$_POST['light'];
	$water=$_POST['water'];
	if (!isset($id)) echo 0;
	if (isset($light) && isset($water)) echo plant::toggle_light_water($id, $light, $water);
	else echo 0;
?>