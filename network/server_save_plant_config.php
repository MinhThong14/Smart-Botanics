<?php
	include "../lib/System_classes.php";
	/*
	 * Test case: uncomment this for testing
	 $plant["id"]=1;
	 $plant["name"]="Tree";
	 $plant["location"]="School";
	 $plant["lux_threshold"]=500;
	 $plant["autoLight"]=true;
	 $plant["light_on_begin"]=13;
	 $plant["light_on_end"]=18;
	 $plant["minMoisture"]=40;
	 $plant["maxMoisture"]=50;
	 $plant["autoWater"]=true;
	 */
	$plant["id"]=$_POST["id"];
	$plant["name"]=$_POST["name"];
	$plant["location"]=$_POST["location"];
	$plant["lux_threshold"]=$_POST["lux_threshold"];
	$plant["autoLight"]=$_POST["autoLight"];
	$plant["light_on_begin"]=$_POST["light_on_begin"];
	$plant["light_on_end"]=$_POST["light_on_end"];
	$plant["minMoisture"]=$_POST["minMoisture"];
	$plant["maxMoisture"]=$_POST["maxMoisture"];
	$plant["autoWater"]=$_POST["autoWater"];
	echo plant::save_config($plant);
?>