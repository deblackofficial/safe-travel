#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <SPI.h>
#include <MFRC522.h>

#define RST_PIN D1
#define SS_PIN  D2
MFRC522 mfrc522(SS_PIN, RST_PIN);

#define LED_PIN     D3    
#define BUZZER_PIN  D4    

const char* ssid     = "Mannaz";
const char* password = "mom11123";

const char* registrationServer = "http://172.20.10.3/prj/Dashboard/register_bus.php";
const char* journeyServer = "http://172.20.10.3/prj/Dashboard/save_bus.php";

String lastUID = "";
unsigned long lastScanTime = 0;
const unsigned long SCAN_COOLDOWN = 2000;
bool journeyActive = false;

void setup() {
  Serial.begin(115200);
  pinMode(LED_PIN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  
  connectToWiFi();
  
  SPI.begin();
  mfrc522.PCD_Init();
  Serial.println("RFID Reader Ready");
  Serial.println("Place RFID cards near reader for registration or journey management");
  startupBeep();
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi disconnected, reconnecting...");
    connectToWiFi();
  }

  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String uid = getCardUID();
    
    if (uid != lastUID || millis() - lastScanTime > SCAN_COOLDOWN) {
      lastUID = uid;
      lastScanTime = millis();
      
      Serial.println("\n=== RFID Tag Detected ===");
      Serial.println("UID: " + uid);
      scanFeedback();
      
      if (reportRFIDToRegistration(uid)) {
        handleJourneyStatus(uid);
      } else {
        Serial.println("Failed to report RFID to registration system");
        errorBeep();
      }
    }
    mfrc522.PICC_HaltA();
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

bool reportRFIDToRegistration(String uid) {
  Serial.println("Reporting to registration system...");
  WiFiClient client;
  HTTPClient http;
  
  String url = String(registrationServer) + "?report_rfid=1&uid=" + uid;
  http.begin(client, url);
  http.setTimeout(5000);
  
  int httpCode = http.GET();
  if (httpCode == HTTP_CODE_OK) {
    String response = http.getString();
    Serial.println("Registration Response: " + response);
    
    DynamicJsonDocument doc(256);
    DeserializationError error = deserializeJson(doc, response);
    
    if (!error) {
      const char* status = doc["status"];
      if (strcmp(status, "success") == 0) {
        Serial.println("RFID available for registration");
        availableBeep();
      } else if (strcmp(status, "already_registered") == 0) {
        Serial.println("RFID already registered to bus");
        const char* plateNumber = doc["bus"]["plate_number"];
        Serial.println("Bus: " + String(plateNumber));
      }
    } else {
      Serial.println("JSON parsing error: " + String(error.c_str()));
    }
    
    http.end();
    return true;
  } else {
    Serial.println("Registration HTTP Error: " + String(httpCode));
    http.end();
    return false;
  }
}

void handleJourneyStatus(String uid) {
  Serial.println("Checking journey status...");
  WiFiClient client;
  HTTPClient http;
  
  String url = String(journeyServer) + "?uid=" + uid + "&journey_active=" + (journeyActive ? "1" : "0");
  http.begin(client, url);
  http.setTimeout(5000);
  
  int httpCode = http.GET();
  if (httpCode > 0) {
    String response = http.getString();
    Serial.println("Journey Response: " + response);
    processServerResponse(response);
  } else {
    Serial.println("Journey HTTP Error: " + String(httpCode));
    connectionErrorBeep();
  }
  http.end();
}

void processServerResponse(String response) {
  DynamicJsonDocument doc(512);
  DeserializationError error = deserializeJson(doc, response);
  if (error) {
    Serial.println("JSON parsing failed: " + String(error.c_str()));
    errorBeep();
    return;
  }

  const char* status = doc["status"];
  const char* message = doc["message"];
  
  Serial.println("Status: " + String(status));
  if (message) {
    Serial.println("Message: " + String(message));
  }

  if (strcmp(status, "journey_started") == 0) {
    journeyActive = true;
    Serial.println("*** JOURNEY STARTED ***");
    if (doc.containsKey("bus")) {
      const char* plateNumber = doc["bus"]["plate_number"];
      int capacity = doc["bus"]["capacity"];
      Serial.println("Bus: " + String(plateNumber) + " (Capacity: " + String(capacity) + ")");
    }
    journeyStartBeep();
  } 
  else if (strcmp(status, "journey_ended") == 0) {
    journeyActive = false;
    Serial.println("*** JOURNEY ENDED ***");
    if (doc.containsKey("bus")) {
      const char* plateNumber = doc["bus"]["plate_number"];
      Serial.println("Bus: " + String(plateNumber));
    }
    journeyEndBeep();
  }
  else if (strcmp(status, "available") == 0) {
    Serial.println("*** RFID AVAILABLE FOR REGISTRATION ***");
    availableBeep();
  }
  else if (strcmp(status, "error") == 0) {
    Serial.println("Server Error: " + String(message));
    errorBeep();
  }
}

void connectToWiFi() {
  Serial.println("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi Connected!");
    Serial.println("IP Address: " + WiFi.localIP().toString());
    Serial.println("Signal Strength: " + String(WiFi.RSSI()) + " dBm");
    wifiConnectedBeep();
  } else {
    Serial.println("\nWiFi Connection Failed!");
    wifiErrorBeep();
  }
}

// Feedback functions
void startupBeep() {
  beep(100); delay(100); beep(100); delay(100); beep(100);
  Serial.println("System ready!");
}

void scanFeedback() {
  digitalWrite(LED_PIN, HIGH);
  beep(50);
  digitalWrite(LED_PIN, LOW);
}

void availableBeep() {
  beep(200); delay(100); beep(200);
  Serial.println("♪ Available beep");
}

void journeyStartBeep() {
  for (int i = 0; i < 3; i++) {
    tone(BUZZER_PIN, 800 + (i * 200), 300);
    digitalWrite(LED_PIN, HIGH);
    delay(300);
    digitalWrite(LED_PIN, LOW);
    delay(100);
  }
  Serial.println("♪ Journey start beep");
}

void journeyEndBeep() {
  for (int i = 0; i < 3; i++) {
    tone(BUZZER_PIN, 1400 - (i * 200), 300);
    digitalWrite(LED_PIN, HIGH);
    delay(300);
    digitalWrite(LED_PIN, LOW);
    delay(100);
  }
  Serial.println("♪ Journey end beep");
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
  Serial.println("♪ Error beep");
}

void connectionErrorBeep() {
  tone(BUZZER_PIN, 300, 1500);
  digitalWrite(LED_PIN, HIGH);
  delay(1500);
  digitalWrite(LED_PIN, LOW);
  noTone(BUZZER_PIN);
  Serial.println("♪ Connection error beep");
}

void wifiConnectedBeep() {
  beep(150); delay(50); beep(150); delay(50); beep(150);
  Serial.println("♪ WiFi connected beep");
}

void wifiErrorBeep() {
  tone(BUZZER_PIN, 200, 2000);
  digitalWrite(LED_PIN, HIGH);
  delay(2000);
  digitalWrite(LED_PIN, LOW);
  noTone(BUZZER_PIN);
  Serial.println("♪ WiFi error beep");
}

void beep(int duration) {
  digitalWrite(LED_PIN, HIGH);
  digitalWrite(BUZZER_PIN, HIGH);
  delay(duration);
  digitalWrite(BUZZER_PIN, LOW);
  digitalWrite(LED_PIN, LOW);
}