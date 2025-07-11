#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ArduinoJson.h>
#include <SPI.h>
#include <MFRC522.h>

// RFID pins
#define RST_PIN D1
#define SS_PIN  D2
MFRC522 mfrc522(SS_PIN, RST_PIN);

// LED & Buzzer pins
#define LED_PIN     D3    
#define BUZZER_PIN  D4    

const char* ssid     = "Mannaz";       
const char* password = "mom11123";     
const char* server = "http://172.20.10.3/prj/save_card_uid.php"; 



String lastUID = "";
unsigned long lastScanTime = 0;
const unsigned long SCAN_COOLDOWN = 3000; // 3 seconds between same card scans

void setup() {
  Serial.begin(115200);
  pinMode(LED_PIN, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);
  digitalWrite(BUZZER_PIN, LOW);

  WiFi.begin(ssid, password);
  Serial.print("Connecting to Wi-Fi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500); 
    Serial.print(".");
  }
  Serial.println("\nWiFi connected");
  Serial.println("IP: " + WiFi.localIP().toString());

  SPI.begin();
  mfrc522.PCD_Init();
  Serial.println("RFID ready - Waiting for cards...");
  
  // Startup indication
  startupBeep();
}

void loop() {
  if (!mfrc522.PICC_IsNewCardPresent() || !mfrc522.PICC_ReadCardSerial()) {
    return;
  }

  String uid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(mfrc522.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  // Avoid processing same card too quickly
  unsigned long currentTime = millis();
  if (uid == lastUID && (currentTime - lastScanTime) < SCAN_COOLDOWN) {
    mfrc522.PICC_HaltA();
    return;
  }

  lastUID = uid;
  lastScanTime = currentTime;

  Serial.println("=== Card Detected ===");
  Serial.println("UID: " + uid);
  
  // Visual feedback for scan
  scanFeedback();
  
  // Send to server and process response
  sendToServer(uid);
  
  // Small delay before allowing next scan
  delay(1000);
  mfrc522.PICC_HaltA();
}

void sendToServer(String uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi disconnected - reconnecting...");
    WiFi.begin(ssid, password);
    while (WiFi.status() != WL_CONNECTED) {
      delay(500);
      Serial.print(".");
    }
    Serial.println("\nReconnected to WiFi");
  }

  WiFiClient client;
  HTTPClient http;

  String url = String(server) + "?uid=" + uid;
  Serial.println("Sending to: " + url);

  http.begin(client, url);
  http.addHeader("User-Agent", "ESP8266-RFID-Reader");
  
  int httpCode = http.GET();

  if (httpCode > 0) {
    String response = http.getString();
    Serial.println("Server response: " + response);
    
    // Parse JSON response
    processServerResponse(response, uid);
  } else {
    Serial.println("HTTP request failed. Code: " + String(httpCode));
    Serial.println("Error: " + http.errorToString(httpCode));
    connectionErrorBeep();
  }

  http.end();
}

void processServerResponse(String response, String uid) {
  // Parse JSON response
  DynamicJsonDocument doc(1024);
  DeserializationError error = deserializeJson(doc, response);
  
  if (error) {
    Serial.println("JSON parsing failed: " + String(error.c_str()));
    errorBeep();
    return;
  }
  
  String status = doc["status"];
  String message = doc["message"];
  String action = doc["action"];
  
  Serial.println("Status: " + status);
  Serial.println("Message: " + message);
  
  if (status == "available") {
    // Card is available for assignment
    Serial.println("✅ Card ready for assignment");
    availableCardBeep();
    
  } else if (status == "registered") {
    // Card is already registered
    JsonObject user = doc["user"];
    if (user) {
      String userName = user["name"];
      String userEmail = user["email"];
      Serial.println("⚠️  Card already registered to:");
      Serial.println("Name: " + userName);
      Serial.println("Email: " + userEmail);
    }
    registeredCardBeep();
    
  } else if (status == "error") {
    // Error occurred
    Serial.println("❌ Error: " + message);
    errorBeep();
  } else {
    // Unknown response
    Serial.println("Unknown server response");
    errorBeep();
  }
}

// Feedback functions
void startupBeep() {
  // Two short beeps on startup
  digitalWrite(LED_PIN, HIGH);
  digitalWrite(BUZZER_PIN, HIGH);
  delay(100);
  digitalWrite(BUZZER_PIN, LOW);
  delay(100);
  digitalWrite(BUZZER_PIN, HIGH);
  delay(100);
  digitalWrite(BUZZER_PIN, LOW);
  digitalWrite(LED_PIN, LOW);
}

void scanFeedback() {
  // Quick LED flash when card is scanned
  digitalWrite(LED_PIN, HIGH);
  delay(100);
  digitalWrite(LED_PIN, LOW);
}

void availableCardBeep() {
  // Single long beep for available card
  digitalWrite(LED_PIN, HIGH);
  digitalWrite(BUZZER_PIN, HIGH);
  delay(500);
  digitalWrite(BUZZER_PIN, LOW);
  digitalWrite(LED_PIN, LOW);
  Serial.println("🔊 Success beep played");
}

void registeredCardBeep() {
  // Two medium beeps for registered card
  digitalWrite(LED_PIN, HIGH);
  for (int i = 0; i < 2; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(300);
    digitalWrite(BUZZER_PIN, LOW);
    delay(200);
  }
  digitalWrite(LED_PIN, LOW);
  Serial.println("🔊 Registered card beep played");
}

void errorBeep() {
  // Three short error beeps
  digitalWrite(LED_PIN, HIGH);
  for (int i = 0; i < 3; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(150);
    digitalWrite(BUZZER_PIN, LOW);
    delay(150);
  }
  digitalWrite(LED_PIN, LOW);
  Serial.println("🔊 Error beep played");
}

void connectionErrorBeep() {
  // Long error beep for connection issues
  digitalWrite(LED_PIN, HIGH);
  digitalWrite(BUZZER_PIN, HIGH);
  delay(1000);
  digitalWrite(BUZZER_PIN, LOW);
  digitalWrite(LED_PIN, LOW);
  Serial.println("🔊 Connection error beep played");
}