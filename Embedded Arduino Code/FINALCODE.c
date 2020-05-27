#include <Adafruit_CC3000.h>
#include <ccspi.h>
#include <SPI.h>
#include <string.h>

//sensor initilization
#define LDR_pin A4 // ana, input to store data
#define relay_pin 2 //dig, output to control relay
#define moist_pin A1 //ana, input to store data
#define valve_pin 7 //dig, output to control valve

/* DEFINE VARIABLE FOR THE WIFI sheild */
#define ADAFRUIT_CC3000_IRQ   3  // MUST be an interrupt 
// These can be any two pins
#define ADAFRUIT_CC3000_VBAT  5
#define ADAFRUIT_CC3000_CS    10
#define SPI_CLOCK_DIVIDER     14
// Use hardware SPI for the remaining pins
// On an UNO, SCK = 13, MISO = 12, and MOSI = 11
Adafruit_CC3000 WIFI_SHIELD = Adafruit_CC3000(ADAFRUIT_CC3000_CS, ADAFRUIT_CC3000_IRQ, ADAFRUIT_CC3000_VBAT,
                                         SPI_CLOCK_DIVIDER); // you can change this clock speed

#define IDLE_TIMEOUT_MS  5000      // Amount of time to wait (in milliseconds) with no data 
                                   // received before closing the connection.  If you know the server
                                   // you're accessing is quick to respond, you can reduce this value.

/* WIFI NETWORK PARAMETERS */
#define WLAN_SSID "ArBB" //"Nagui's Network" //cannot be longer than 32 characters! Nagui's Network
#define WLAN_PASS "11223344"
#define WLAN_SECURITY   WLAN_SEC_WPA2 // Security can be WLAN_SEC_UNSEC, WLAN_SEC_WEP, WLAN_SEC_WPA or WLAN_SEC_WPA2

/* GLOBAL VARIABLE USED */
// SERVER INFOMATION
#define HOST      "minhmai.5gbfree.com" //The address of the cloud server
#define CONTACT_PAGE "/SmartBotanics/network/server_contact.php" //User to receive data from the server
  
// ID associated with this Arduino Node
#define plantId 1
  
// Lux and Moisture from sensors will be stored in the global values below
uint16_t lux=50;
uint16_t moisture=50;
  
// Instructions from Server will be stored in the global values below
uint16_t lux_threshold=600;
uint16_t autoLight=1;
uint16_t isLightOn=0;
uint16_t minMoisture=40;
uint16_t maxMoisture=70;
uint16_t autoWater=1;
uint16_t isValveOn=0;
   
// Variable determined if the valve should be opened
boolean canWater = false;
  
// The time between collection of data and contacting server
const unsigned long postingInterval= 60L * 1000L;

// last time you connected to the server, in milliseconds
unsigned long lastConnectionTime = 0;       

// Connection count
uint16_t connection_count=0;
#define connection_threshold 8

void setup(void)
{
  Serial.begin(115200);
  //set up pin mode for relay and valve
  pinMode(relay_pin, OUTPUT);
  pinMode(valve_pin, OUTPUT);
  //WIFI shield setup
  while (!WIFI_SHIELD_SETUP())
     {
       Serial.println(F("\n\nDisconnecting"));
       WIFI_SHIELD.disconnect();
       delay(5000);
    }
}
  
void loop(void)
{
   
  //Serial.print("\n--Start connection: ");Serial.print(connection_count);Serial.println("--");
	
  //collect data from sensors
  lux = Light();
  Serial.print("Lux value: ");
  Serial.println(lux);
  moisture = Moisture();
  Serial.print("Moisture value: ");
  Serial.println(moisture);
  
  if (millis() - lastConnectionTime > postingInterval){
    //push light and moisture data to server
    contactServer();
    connection_count++;
  }
  
  
  //delay until next iteration of loop()
  //Reboot WIFI shield after 15 loops time for stability
  if (connection_count==connection_threshold) {
     connection_count=0;
     delay(5000);
     while (!WIFI_SHIELD_SETUP())
     {
       Serial.println(F("\n\nDisconnecting"));
       WIFI_SHIELD.disconnect();
       delay(5000);
    }
  }
}

//light sensor function
int Light(){
  uint16_t lux_value = analogRead(LDR_pin);
	
  //isLightOn has highest priority
  if (isLightOn) digitalWrite(relay_pin, HIGH); 
  else digitalWrite(relay_pin, LOW);
  
  //AUTO LIGHT FEATURE
  if (autoLight && !isLightOn){ 
    if (lux_value < lux_threshold) digitalWrite(relay_pin, HIGH);
    else digitalWrite(relay_pin, LOW);
  }
  
  return lux_value;
}

//moisture sensor function
int Moisture(){
  uint16_t moist_value = analogRead(moist_pin); //Read value from sensor, 0 to 950
  
  if (isValveOn) canWater=true; //check manual instruction from water
  else if (autoWater) canWater=true; // check if AUTO WATER feature is on
  else canWater=false; //else cannot water
  
  
  // Check water threshold
  if(moist_value/10 > maxMoisture) canWater = false;
  if(moist_value/10 < minMoisture) canWater = true;
  
  if (canWater)
  {
     Serial.println("--AutoValveOn--");
     digitalWrite(valve_pin, HIGH);
     //turn off valve after 2sec
     Serial.println("--WaterValve on!--");
     delay(3000);
     Serial.println("--WaterValve off!--");
     digitalWrite(valve_pin, LOW);	
  }
}

/**************************************************************************/
/*!
    @brief  initialize wifi sheid, deletes old connections, and connects to network
*/
/**************************************************************************/
bool WIFI_SHIELD_SETUP(){
  
  Serial.println("Hello, WIFI Shield!");
 
 /* Initialize the WIFI Shield */
 Serial.println("Initializing WIFI SHIELD...");
  if (!WIFI_SHIELD.begin())
  {
    Serial.println("Couldn't begin()! Check your wiring?");
    while(1);
  }
  /* Delete any old connection data on the module */
  Serial.println(F("Deleting old connection profiles"));
  if (!WIFI_SHIELD.deleteProfiles()) {
    Serial.println(F("Failed!"));
    while(1);
  }
  Serial.print("\nAttempting to connect to "); 
  Serial.println(WLAN_SSID);
  if (!WIFI_SHIELD.connectToAP(WLAN_SSID, WLAN_PASS, WLAN_SECURITY)) {
    Serial.println(F("Failed!"));
    while(1);
  }
  
  /* Wait for DHCP to complete */
  uint16_t count=0;
  Serial.println("Request DHCP");
  while (!WIFI_SHIELD.checkDHCP())
  {
    delay(300);
    count++; // Change the timeout to your desired value
    if (count>20) return false;
  }  
  
  /* Display the IP address DNS, Gateway, etc. */  
  return  displayConnectionDetails();
}

/**************************************************************************/
/*!
    @brief  Tries to read the IP address and other connection details
*/
/**************************************************************************/
bool displayConnectionDetails(void)
{
  uint32_t ipAddress, netmask, gateway, dhcpserv, dnsserv;
  
  if(!WIFI_SHIELD.getIPAddress(&ipAddress, &netmask, &gateway, &dhcpserv, &dnsserv))
  {
    Serial.println(F("Unable to retrieve the IP Address!\r\n"));
    return false;
  }
  else
  {
    Serial.print(F("\nIP Addr: ")); WIFI_SHIELD.printIPdotsRev(ipAddress);
    Serial.print(F("\nNetmask: ")); WIFI_SHIELD.printIPdotsRev(netmask);
    Serial.print(F("\nGateway: ")); WIFI_SHIELD.printIPdotsRev(gateway);
    Serial.print(F("\nDHCPsrv: ")); WIFI_SHIELD.printIPdotsRev(dhcpserv);
    Serial.print(F("\nDNSserv: ")); WIFI_SHIELD.printIPdotsRev(dnsserv);
    Serial.println();
    return true;
  }
}

/**************************************************************************/
/*!
    @brief  in house automated browser
*/
/**************************************************************************/
char *WEB_Browser(char *webpage){
  Serial.println("Visit the page:");
  Serial.print(HOST);
  Serial.println(webpage);
  
  int timeout=10000; //reading data for 6 seconds
  unsigned long totalTime=0;
  unsigned long lastRead=millis();
  
  /* Obtain the ip address of the website */
  uint32_t website_ip=0;
  while (website_ip == 0) {
    if (! WIFI_SHIELD.getHostByName(HOST,&website_ip)) {
      Serial.println(F("Couldn't find website IP address!"));
    }
    delay(500);
    totalTime+=(millis()-lastRead);
    if (totalTime>=timeout) return "e";
    lastRead=millis();
  }
  /* Print out the IP address of the website */
  Serial.print("Go To Address: ");
  WIFI_SHIELD.printIPdotsRev(website_ip);
  
  Adafruit_CC3000_Client Browser=WIFI_SHIELD.connectTCP(website_ip,80);
  Browser.setTimeout(10000);
  
  /* Connect a the specific webpage using HTTP protocol */
  if (Browser.connected()){
    Serial.println("\r\nStart sending HTTP message");
    Browser.print(F("GET "));
    Browser.print(webpage);
    Browser.print(F(" HTTP/1.1\r\n"));
    Browser.print(F("Host: ")); 
    Browser.print(HOST);
    Browser.print(F("\r\n"));
    Browser.print(F("Connection: close"));
    Browser.print(F("\r\n"));
    Browser.println(); //Warning: missing this new line will result in failures
  }
  else
  {
    Serial.println("Connection Failed");
  }
  Serial.println("Start reading response");
  /* Read information from the webpage */
  lastRead=millis(); //Record the time when we start reading data
  
  int count=0;
  boolean isOK=false;
  String data="";
  char c='a';
  //Discard any bytes that have been written to the client but not yet read
  while(Browser.connected() && (millis()-lastRead < timeout) && c!='}')
  {
    while (Browser.available() && c!='}')
    {
      c=Browser.read();
      /* make sure that the browser only save important data
         Json data start with a character "{" and end by "}"
      */
      if (c=='}') isOK=false;
      if (isOK) data+=c;
      if (c=='{') isOK=true; 
      totalTime+=(millis()-lastRead);
      if (totalTime>=timeout) return "e";
      lastRead=millis();
    }
    totalTime+=(millis()-lastRead);
    if (totalTime>=timeout) return "e";
    lastRead=millis();
  }
  /* Close Browser */
  Browser.close();
  lastConnectionTime = millis();
  int data_len= data.length()+1;
  char data_array[data_len];
  data.toCharArray(data_array,data_len);
  return data_array;
}

/**************************************************************************/
/*!
    Contact the server
*/
/**************************************************************************/
/**************************************************************************/
void contactServer()
{
    //Create contact Link
    String contactLink=CONTACT_PAGE;
    contactLink.concat("?id=");
    contactLink.concat(plantId);
    contactLink.concat("&moisture=");
    contactLink.concat(moisture);
    contactLink.concat("&light=");
    contactLink.concat(lux);
    
    //FECTCH DATA FROM SERVER 
    Serial.println("\nContact the server using the link:");
    Serial.println(contactLink);
    
    //Convert a string to array of characters
    uint16_t contactLink_len= contactLink.length()+1;
    char webpagePull[contactLink_len];
    contactLink.toCharArray(webpagePull,contactLink_len);  
    char *encoded = WEB_Browser(webpagePull);
      
  
    Serial.println("Below are code receive from the Server: ");
    Serial.println(encoded);
    if (encoded[0]!='e'){ //if there is no error then decode the message
      
     uint16_t instructions[7];
     boolean flag;
     instruction_decoder(encoded,instructions,&flag);
    
     if (!flag) return;
     
     lux_threshold=instructions[0];
     autoLight=instructions[1];
     isLightOn=instructions[2];
     minMoisture=instructions[3];
     maxMoisture=instructions[4];
     autoWater=instructions[5];
     isValveOn=instructions[6];
   
     Serial.println("Instructions from the server:");
     Serial.print("Lux_threshold: ");Serial.println(lux_threshold);
     Serial.print("minMoisture: ");Serial.println(minMoisture);
     Serial.print("maxMoisture: ");Serial.println(maxMoisture);
     Serial.print("autoLight: ");Serial.println(autoLight);
     Serial.print("autoWater: ");Serial.println(autoWater);
     Serial.print("isValveOn: ");Serial.println(isValveOn);
     Serial.print("isLightOn: ");Serial.println(isLightOn);
   }
}

void instruction_decoder(char *encoded, uint16_t instructions[], boolean *flag)
{
  uint16_t m=0;
  char temp_value[7];
  uint16_t n=0;
  
  for(int i = 0; i<=strlen(encoded); i++)
  {
    if (encoded[i]==';'){
      temp_value[n++]='\0';
      instructions[m++]=atoi(temp_value);
      n=0;
    }
    else temp_value[n++]=encoded[i];
  }
  if (m<7) *flag=false;
  else *flag=true;
}
