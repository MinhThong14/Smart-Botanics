<?php	
	include "Helper_functions.php";
	class User {
		private $username;
		private $plants=array();
		private $sqlHandler;
		
		/* The constructor to create the user class
		 * Input: username
		 */
		public function __construct($username){
			$this->username=$username;
			$this->sqlHandler=$createSQLHandler();
			loadPlants();
		}
		
		/* Function loading up all the plants that the user owns
		 * Return: the list of plants
		 */
		private function loadPlants(){
			$query="SELECT id FROM plants";
			$result=$sqlHandler->query($query);
			if ($result){
				while ($row = $result->fetch_assoc()) {
					// add a plant to the list of plants
					array_push($plants,new Plant($row["id"]));
				}
			}
			$result->free();
		}
		
		/* Method changePassword to change the password of the user
		 * Input: a new password
		 */
		public function changePassword($password){
			
		}
		
		/* Method changeEmail to change the email of the user
		 * Input: a new email
		 */
		public function changeEmail($email){
			
		}
		
		/* Method getPlants
		 * Return: the list of plants that the user owns
		 */
		public function getPlants(){
			
		}
		
		/* Method addPlant to add a new plant for the user
		 * input: a plant
		 */
		public function addPlant($plant){
			
		}
		
		/* Method validateUser
		 * Static Method to validate the user login with username and password
		 * Input: username, password
		 * Return: 
		 * 			-1: username is invalid
		 * 			 0: password is wrong
		 *			 1: password is valid
		 */
		public static function validateUser($username,$password)
		{
			$sqlHandler=createSQLHandler();
			$query='SELECT password FROM accounts WHERE username="'.$username.'"';
			$result=$sqlHandler->query($query);
			removeSQLHandler($sqlHandler);
			if ($result) {
				$row= $result->fetch_assoc();
				if ($row==null){
					return -1;
				}
				if ($row['password']==$password){
					return 1;
				}else{
					return 0;
				}
			}
		}
	}
	class plant {
		private $plantId;
		private $ipAddress;
		
		/* The constructor to create a networkHandler
		 * Input: username
		 */
		public function __construct($plantId){
			$this->plantId=$plantId;
			
			//Obtain the ipAddress for the networkHandler
			// Not done yet.....
		}
		
		/* Function get ipAddress
		 * return the ipAddress of the networkHandler
		 */
		public function getIpAdress(){
			return $this->ipAddress;
		}
		
		/* Function receive_data_from_node
		 * Recieve data sent from the arduino node
		 * Require: + plantId to identify the node
		 * 			+ lightLevel sent from the node
		 * 			+ moistureLevel sent from the node
		 * Return:  + 1 if receive data successfully
		 * 			+ 0 if receive data unsuccessfully
		 */			
		public static function receive_data_from_node($plantId,$moistureLevel,$lightLevel)
		{
			$sqlHandler=createSQLHandler();
			$moistureLevel=(int)$moistureLevel/10; // transfer to percentage
			if ($moistureLevel > 100 || $lightLevel > 2400) return 1; // the value is not good
			
			//fetch some data about light level
			$query=	'SELECT lux_threshold,light,update_frequency,light_on_begin,light_on_end, lightData, moistureData FROM plants WHERE id='.$plantId;
			$result=$sqlHandler->query($query);
			if ($result){
				$row=$result->fetch_assoc();
				
				$lux_threshold=(int)$row['lux_threshold'];
				$light_time=(float)$row['light'];
				$light_on_begin=(int)$row['light_on_begin'];
				$light_on_end=(int)$row['light_on_end'];
				$update_frequency=(int)$row['update_frequency'];
				
				$lightData = json_decode($row['lightData']);
				$moistureData= json_decode($row['moistureData']);
				
				if ($lightData==null) $lightData=array();
				if ($moistureData==null) $moistureData=array();
				
				// If this is mid-night, reset $light_level
				if( date('H') == 0 && date('i') == 0) {
					$light_time=0;
				}
				$total_time=($light_on_end-$light_on_begin)*3600;
				// Increase $light level
				if ($lightLevel>=$lux_threshold) $light_time+=($update_frequency/$total_time) *100;
				$light_time=round($light_time,2);
				
				$current_light_data= new stdClass();
				$current_moisture_data= new stdClass();
				
				$current_light_data->x=date('c');
				$current_light_data->y=$light_time;
				$current_moisture_data->x=date('c');
				$current_moisture_data->y=$moistureLevel;
				
				if (count($lightData)>=10) array_shift($lightData);
				if (count($moistureData)>=10) array_shift($moistureData);
				
				// Adding values to light and moisture record
				array_push($lightData, $current_light_data);
				array_push($moistureData, $current_moisture_data);
				//Transfer light and moisture record back to text
				$lightData=json_encode($lightData);
				$moistureData=json_encode($moistureData);
			}
			else return 0;
			$query='UPDATE plants SET moisture='.$moistureLevel.',light_lux='.$lightLevel.',light='.$light_time.',lightData=\''.$lightData.'\',moistureData=\''.$moistureData.'\' WHERE id='.$plantId;
			$result=$sqlHandler->query($query);
			removeSQLHandler($sqlHandler);
			if ($result) return 1;
			else return 0;
		}
		/* Function send_data_to_node
		 * Send instructions to the node controlling the plant
		 * Require: + plantId to identify the node
		 * 	
		 * Return:  + Json encoded string containing instructions
		 */
		public static function send_data_to_node($plantId)
		{
			$sqlHandler=createSQLHandler();
			$query=	'SELECT lux_threshold,autoLight,light_on_begin,light_on_end,isLightOn,isManualLightOn,'.
					'minMoisture,maxMoisture,autoWater,isValveOn '.
					'FROM plants WHERE id='.$plantId;
			$result=$sqlHandler->query($query);
			removeSQLHandler($sqlHandler);
			if ($result){
				$row=$result->fetch_assoc();
				//get the current time in hour
				date_default_timezone_set("America/Toronto");
				$time_in_hour=(int)date("H");
				$row['light_on_begin']=(int)$row['light_on_begin'];
				$row['light_on_end']=(int)$row['light_on_end'];
				//Turn on the Light if the time fall into the light on time
				if ($time_in_hour>=$row['light_on_begin'] && $time_in_hour<$row['light_on_end']){
					$row['isLightOn']=1;
				}
				unset($row['light_on_begin']);
				unset($row['light_on_end']);
				$output_string="{";
				foreach ($row as $element)
				{
					$output_string=$output_string.$element.';';
				}
				$output_string=$output_string."}\n";
				return $output_string;
			}
			else return 'e';//return error
		}
		/* Function send_available_plants_to_interface
		 * Send a list of plants that associated with a username to the user-interface
		 * Require: + username
		 * Return: a list of plants in json format
		 */
		public static function send_available_plants_to_interface($username)
		{
			$sqlHandler=createSQLHandler();
			$query='SELECT * FROM plants WHERE username="'.$username.'"';
			$result=$sqlHandler->query($query);
			removeSQLHandler($sqlHandler);
			if ($result){
				$plant_list=array();
				while ($row = $result->fetch_assoc())
				{
					array_push($plant_list,$row);
				}
				return json_encode($plant_list);
			}
			else return null;
		}
		/* Function send_properties_to_interface
		 * Send a set of properties of the plants to the user interface
		 * Require: + plantId
		 * Return: a set of properties of the plant
		 */
		public static function send_properties_to_interface($plantId)
		{
			$sqlHandler=createSQLHandler();
			$query='SELECT * FROM plants WHERE id='.$plantId;
			$result=$sqlHandler->query($query);
			removeSQLHandler($sqlHandler);
			if ($result){
				$row=$result->fetch_assoc();
				return json_encode($row);
			}
			else return null;
		}
		/* Function save configuration of a plant
		 * Save a new configuration of the plant into database
		 * Require: + object plant contant all necessary data
		 * Return: true - succesful, false- otherwise
		 */
		public static function save_config($plant)
		{
			$sqlHandler=createSQLHandler();
			$query='UPDATE plants SET '.
					'name="'.$plant["name"].'"'.
					',location="'.$plant["location"].'"'.
					',lux_threshold='.$plant["lux_threshold"].
					',autoLight='.$plant["autoLight"].
					',light_on_begin='.$plant["light_on_begin"].
					',light_on_end='.$plant["light_on_end"].
					',minMoisture='.$plant["minMoisture"].
					',maxMoisture='.$plant["maxMoisture"].
					',autoWater='.$plant["autoWater"].
					' WHERE id='.$plant["id"];
			$result=$sqlHandler->query($query);
			removeSQLHandler($sqlHandler);
			if ($result) return 1;
			else return 0;
		}
		/* Function add new plant
		 * Add a new plant to the database
		 * If plant is already exist, return the property of the plant
		 * Requires: + Plant'ID
		 * Return: plant object or empty
		 */
		public static function add_new_plant($id,$username)
		{	
			$sqlHandler=createSQLHandler();
			$query='INSERT INTO plants (id,name,location,username,minMoisture,maxMoisture,lux_threshold,light_on_begin,light_on_end)'.
					' VALUES ('.$id.',"New Plant","N/A","'.$username.'",60,100,600,9,16);';
			$result=$sqlHandler->query($query);
			removeSQLHandler($sqlHandler);
			return plant::send_properties_to_interface($id);
		}
		/* Function toggle light or water
		 * Turn on or Turn off Light or water 
		 * Requires: + Light signal indicating light is toggled
		 * 			 + Water signal indicating water is toggled
		 * Return: - 0 means not ok
		 * 		   - 1 means ok
		 */
		public static function toggle_light_water($id,$light,$water)
		{
			$sqlHandler=createSQLHandler();
			$query='SELECT isManualLightOn,isValveOn FROM plants WHERE id="'.$id.'"';
			$result=$sqlHandler->query($query);
			if ($result){
				$row=$result->fetch_assoc();
				$isManualLightOn=(boolean)$row['isManualLightOn'];
				$isValveOn=(boolean)$row['isValveOn'];
				if ($light) $isManualLightOn=!$isManualLightOn;
				if ($water) $isValveOn=!$isValveOn;
			}
			else return 0;
			$query='UPDATE plants SET isManualLightOn='.(int)$isManualLightOn.',isValveOn='.(int)$isValveOn.' WHERE id='.$id;
			$result=$sqlHandler->query($query);
			if ($result) return 1;
			else return 0;
			removeSQLHandler($sqlHandler);
		}
		
		/* Function send_light_value
		 * Require: + id
		 * Return: the light value
		 */
		public static function send_light_value($id)
		{
			$sqlHandler=createSQLHandler();
			$query='SELECT light_lux FROM plants WHERE id="'.$id.'"';
			$result=$sqlHandler->query($query);
			removeSQLHandler($sqlHandler);
			if ($result){
				$row = $result->fetch_assoc();
				return $row['light_lux'];
			}
			else return null;
		}
		/* Function send_moisture_value
		 * Require: + id
		 * Return: the moisture value
		 */
		public static function send_moisture_value($id)
		{
			$sqlHandler=createSQLHandler();
			$query='SELECT moisture FROM plants WHERE id="'.$id.'"';
			$result=$sqlHandler->query($query);
			removeSQLHandler($sqlHandler);
			if ($result){
				$row = $result->fetch_assoc();
				return $row['moisture'];
			}
			else return null;
		}
	}
	//echo plant::toggle_light_water(1,1,0);
	//echo plant::send_data_to_node(1);
	//echo plant::add_new_plant(15,"minhmai");
	//echo plant::send_properties_to_interface(12);
	//echo  User::validateUser("minhmai", "abcdef");
	//echo  plant::receive_data_from_node(1,560,800);
	//echo  plant::server_return_available_plants("minhmai");
?>