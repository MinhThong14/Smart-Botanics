<?php
	include "../lib/System_classes.php";
	header('Content-Type:text/plain');
	
	/* For testing */
	$id=$_GET['id'];
	$moisture=$_GET['moisture'];
	$light=$_GET['light'];
	/**/
	/* REAL
	$id=$_POST['id'];
	$moisture=$_POST['moisture'];
	$light=$_POST['light'];
	*/
	
	if (isset($id) && isset($moisture)  && isset($light)){
		if (plant::receive_data_from_node($id, $moisture, $light))
			echo plant::send_data_to_node($id);
		else return 'e';
	}
	else echo 'e'; //Tell client that there is an error
?>