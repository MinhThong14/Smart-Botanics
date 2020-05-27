<?php
	include "System_variables.inc";
	/*
 	* This file contains all the helper functions for the system
 	*/

	/* Function creating a sqlHandler for the class
	 * Return: the sqlHandler
 	*/
	function createSQLHandler(){
		//create a mysql handler
		$sqlHandler= new mysqli(SQL_SERVER,SQL_USERNAME,SQL_PASSWORD,SQL_DATABASE);
		//check connection
		if ($sqlHandler->connect_error){
			die("[Class/User/mysqlHandler] Connection to SQL server failed:". $sqlHandler->connect_error);
		}
		return $sqlHandler;
	}
	/* Function removing the sqlHandler for the class
	 * Input: the sqlHandler
	 */
	function removeSQLHandler($sqlHandler){
		$sqlHandler->close();
	}
	
?>