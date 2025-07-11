#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <SPI.h>
#include <MFRC522.h>

#define RST_PIN D1
#define SS_PIN D2
MFRC522 mfrc522(SS_PIN, RST_PIN);

#define LED_PIN D3    
#define BUZZER_PIN D4    

const char* ssid = "Mannaz";
const char* password = "mom11123";

const char* serverBase = "http://172.20.10.3/prj/Dashboard/";
const char* scanBusEndpoint = "bus_boarding.php?api=1&scan_bus=1";
const char* checkCardEndpoint = "bus_boarding.php?api=1&check_card=1";
const char* boardPassengerEndpoint = "bus_boarding.php?api=1";

String lastUID = "";
unsigned long lastScanTime = 0;
const unsigned long SCAN_COOLDOWN = 2000;
bool journeyActive = false;
String currentBusId = "";
unsigned long lastWifiAttempt = 0;
const unsigned long WIFI_RETRY_INTERVAL = 30000;

void setup() {
  Serial.begin(115200);
  pinMode(LED_PIN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  
  connectToWiFi();
  
  SPI.begin();
  mfrc522.PCD_Init();
  mfrc522.PCD_SetAntennaGain(mfrc522.RxGain_max);
  Serial.println("\nRFID Reader Ready");
  Serial.println("Place RFID cards near reader");
  startupBeep();
}

void loop() {
  if (WiFi.status() != WL_CONNECTED && millis() - lastWifiAttempt > WIFI_RETRY_INTERVAL) {
    Serial.println("WiFi disconnected, reconnecting...");
    connectToWiFi();
    lastWifiAttempt = millis();
  }

  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String uid = getCardUID();
    
    if (uid != lastUID || millis() - lastScanTime > SCAN_COOLDOWN) {
      lastUID = uid;
      lastScanTime = millis();
      
      Serial.println("\n=== RFID Tag Detected ===");
      Serial.println("UID: " + uid);
      scanFeedback();
      
      checkCardType(uid);
    }
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
  }
  delay(100);
}

String getCardUID() {
  String uid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    uid += String(mfrc522.uid.uidByte[i] < 0x10 ? "0" : "");
    uid += String(mfrc522.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();
  return uid;
}

void checkCardType(String uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Cannot check card - WiFi disconnected");
    connectionErrorBeep();
    return;
  }

  WiFiClient client;
  HTTPClient http;
  
  String url = String(serverBase) + checkCardEndpoint + "&card_uid=" + uid;
  http.begin(client, url);
  http.setTimeout(10000);
  
  int httpCode = http.GET();
  
  if (httpCode == HTTP_CODE_OK) {
    String response = http.getString();
    response.trim();
    Serial.println("Card Check Response: " + response);
    
    if (response.length() == 0) {
      Serial.println("Error: Empty response from server");
      errorBeep();
      http.end();
      return;
    }

    DynamicJsonDocument doc(1024);
    DeserializationError error = deserializeJson(doc, response);
    
    if (error) {
      Serial.print("JSON parsing error: ");
      Serial.println(error.c_str());
      Serial.println("Response content: " + response);
      errorBeep();
      http.end();
      return;
    }
    
    const char* status = doc["status"];
    
    if (strcmp(status, "bus") == 0) {
      handleBusScan(uid);
    } 
    else if (strcmp(status, "passenger") == 0) {
      if (journeyActive && currentBusId != "") {
        handlePassengerScan(uid);
      } else {
        Serial.println("No active trip - cannot board passenger");
        noTripBeep();
      }
    }
    else {
      Serial.println("Unknown card type");
      errorBeep();
    }
  } else {
    Serial.print("Card Check HTTP Error: ");
    Serial.println(httpCode);
    if (httpCode == -1) {
      Serial.println("Connection failed");
    }
    connectionErrorBeep();
  }
  http.end();
}

void handleBusScan(String uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Cannot scan bus - WiFi disconnected");
    connectionErrorBeep();
    return;
  }

  WiFiClient client;
  HTTPClient http;
  
  String url = String(serverBase) + scanBusEndpoint + "&rfid_uid=" + uid;
  http.begin(client, url);
  http.setTimeout(15000);
  
  int httpCode = http.GET();
  
  if (httpCode == HTTP_CODE_OK) {
    String response = http.getString();
    response.trim();
    Serial.println("Bus Scan Response: " + response);
    
    if (response.length() == 0) {
      Serial.println("Error: Empty response from server");
      errorBeep();
      http.end();
      return;
    }

    DynamicJsonDocument doc(1024);
    DeserializationError error = deserializeJson(doc, response);
    
    if (error) {
      Serial.print("JSON parsing error: ");
      Serial.println(error.c_str());
      Serial.println("Response content: " + response);
      errorBeep();
      http.end();
      return;
    }
    
    const char* status = doc["status"];
    
    if (strcmp(status, "trip_started") == 0) {
      journeyActive = true;
      currentBusId = String(doc["bus"]["id"]);
      Serial.println("*** TRIP STARTED ***");
      Serial.println("Bus: " + String(doc["bus"]["plate_number"]));
      Serial.println("Route: " + String(doc["bus"]["start_point"]) + " to " + String(doc["bus"]["end_point"]));
      Serial.println("Capacity: " + String(doc["bus"]["capacity"]));
      Serial.println("Remaining capacity: " + String(doc["remaining_capacity"]));
      journeyStartBeep();
    } 
    else if (strcmp(status, "trip_ended") == 0) {
      journeyActive = false;
      currentBusId = "";
      Serial.println("*** TRIP ENDED ***");
      Serial.println("Passengers carried: " + String(doc["passenger_count"]));
      Serial.println("Duration: " + String(doc["duration"]) + " seconds");
      journeyEndBeep();
    }
    else if (strcmp(status, "error") == 0) {
      const char* message = doc["message"];
      Serial.println("Error: " + String(message));
      
      if (strstr(message, "route_id") != nullptr) {
        Serial.println("This bus needs to be assigned to a route first");
        routeErrorBeep();
      } else {
        errorBeep();
      }
    }
    else {
      Serial.println("Unexpected response status: " + String(status));
      errorBeep();
    }
  } else {
    Serial.print("Bus Scan HTTP Error: ");
    Serial.println(httpCode);
    if (httpCode == -1) {
      Serial.println("Connection failed");
    }
    connectionErrorBeep();
  }
  http.end();
}

void handlePassengerScan(String uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Cannot scan passenger - WiFi disconnected");
    connectionErrorBeep();
    return;
  }

  WiFiClient client;
  HTTPClient http;
  
  String url = String(serverBase) + boardPassengerEndpoint;
  http.begin(client, url);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.setTimeout(15000);
  
  String postData = "bus_id=" + currentBusId + "&card_uid=" + uid;
  int httpCode = http.POST(postData);
  
  if (httpCode == HTTP_CODE_OK) {
    String response = http.getString();
    response.trim();
    Serial.println("Boarding Response: " + response);
    
    if (response.length() == 0) {
      Serial.println("Error: Empty response from server");
      errorBeep();
      http.end();
      return;
    }

    DynamicJsonDocument doc(1024);
    DeserializationError error = deserializeJson(doc, response);
    
    if (error) {
      Serial.print("JSON parsing error: ");
      Serial.println(error.c_str());
      Serial.println("Response content: " + response);
      errorBeep();
      http.end();
      return;
    }
    
    const char* status = doc["status"];
    
    if (strcmp(status, "success") == 0) {
      const char* action = doc["action"];
      
      if (strcmp(action, "boarded") == 0) {
        const char* busStatus = doc["bus_status"];
        
        if (strcmp(busStatus, "over_limit") == 0) {
          Serial.println("Passenger boarded as OVER LIMIT");
          overLimitBeep();
        } else {
          Serial.println("Passenger boarded successfully");
          passengerBoardedBeep();
        }
        Serial.println("Current passengers: " + String(doc["current_passengers"]) + 
                       "/" + String(doc["max_capacity"]));
      }
      else if (strcmp(action, "exited") == 0) {
        Serial.println("Passenger exited");
        passengerExitedBeep();
        Serial.println("Current passengers: " + String(doc["current_passengers"]) + 
                       "/" + String(doc["max_capacity"]));
      }
    } else {
      const char* message = doc["message"];
      Serial.println("Error: " + String(message));
      errorBeep();
    }
  } else {
    Serial.print("Boarding HTTP Error: ");
    Serial.println(httpCode);
    if (httpCode == -1) {
      Serial.println("Connection failed");
    }
    connectionErrorBeep();
  }
  http.end();
}

void connectToWiFi() {
  Serial.println("Connecting to WiFi...");
  WiFi.disconnect();
  WiFi.begin(ssid, password);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    digitalWrite(LED_PIN, !digitalRead(LED_PIN)); // Toggle LED while connecting
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi Connected!");
    Serial.print("SSID: ");
    Serial.println(WiFi.SSID());
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
    Serial.print("Signal Strength: ");
    Serial.println(WiFi.RSSI());
    wifiConnectedBeep();
    digitalWrite(LED_PIN, LOW);
  } else {
    Serial.println("\nWiFi Connection Failed!");
    wifiErrorBeep();
    digitalWrite(LED_PIN, LOW);
  }
}

// Feedback functions
void startupBeep() {
  beep(100); delay(100); beep(100); delay(100); beep(100);
}

void scanFeedback() {
  digitalWrite(LED_PIN, HIGH);
  beep(50);
  digitalWrite(LED_PIN, LOW);
}

void journeyStartBeep() {
  for (int i = 0; i < 3; i++) {
    tone(BUZZER_PIN, 800 + (i * 200), 300);
    digitalWrite(LED_PIN, HIGH);
    delay(300);
    digitalWrite(LED_PIN, LOW);
    delay(100);
  }
}

void journeyEndBeep() {
  for (int i = 0; i < 3; i++) {
    tone(BUZZER_PIN, 1400 - (i * 200), 300);
    digitalWrite(LED_PIN, HIGH);
    delay(300);
    digitalWrite(LED_PIN, LOW);
    delay(100);
  }
}

void passengerBoardedBeep() {
  beep(200); delay(100); beep(200); delay(100); beep(200);
}

void passengerExitedBeep() {
  beep(200); delay(50); beep(200); delay(50); beep(200);
}

void overLimitBeep() {
  for (int i = 0; i < 3; i++) {
    tone(BUZZER_PIN, 300, 200);
    digitalWrite(LED_PIN, HIGH);
    delay(200);
    digitalWrite(LED_PIN, LOW);
    delay(100);
  }
}

void noTripBeep() {
  tone(BUZZER_PIN, 300, 1000);
  digitalWrite(LED_PIN, HIGH);
  delay(1000);
  digitalWrite(LED_PIN, LOW);
  noTone(BUZZER_PIN);
}

void errorBeep() {
  for (int i = 0; i < 5; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    digitalWrite(LED_PIN, HIGH);
    delay(100);
    digitalWrite(BUZZER_PIN, LOW);
    digitalWrite(LED_PIN, LOW);
    delay(100);
  }
}

void routeErrorBeep() {
  for (int i = 0; i < 2; i++) {
    tone(BUZZER_PIN, 300, 200);
    digitalWrite(LED_PIN, HIGH);
    delay(200);
    digitalWrite(LED_PIN, LOW);
    delay(100);
    tone(BUZZER_PIN, 500, 200);
    digitalWrite(LED_PIN, HIGH);
    delay(200);
    digitalWrite(LED_PIN, LOW);
    delay(100);
  }
}

void connectionErrorBeep() {
  tone(BUZZER_PIN, 300, 1500);
  digitalWrite(LED_PIN, HIGH);
  delay(1500);
  digitalWrite(LED_PIN, LOW);
  noTone(BUZZER_PIN);
}

void wifiConnectedBeep() {
  beep(150); delay(50); beep(150); delay(50); beep(150);
}

void wifiErrorBeep() {
  tone(BUZZER_PIN, 200, 2000);
  digitalWrite(LED_PIN, HIGH);
  delay(2000);
  digitalWrite(LED_PIN, LOW);
  noTone(BUZZER_PIN);
}

void beep(int duration) {
  digitalWrite(LED_PIN, HIGH);
  digitalWrite(BUZZER_PIN, HIGH);
  delay(duration);
  digitalWrite(BUZZER_PIN, LOW);
  digitalWrite(LED_PIN, LOW);
}