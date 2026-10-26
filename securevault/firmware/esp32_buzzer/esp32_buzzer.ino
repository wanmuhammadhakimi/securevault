/*
 * SecureVault - ESP32 intrusion buzzer
 *
 * Polls the LIVE server (Raspberry Pi behind Cloudflare) over HTTPS:
 *   https://app.securevault.com.my/api/intrusion_alert.php?action=status
 * so it works on ANY WiFi with internet, and does not depend on the Pi's or
 * the laptop's local IP address.
 *
 * WiFi: remembers several networks (campus, home, phone hotspot) and joins
 * whichever one is in range. ESP32 supports 2.4 GHz only.
 */
#include <WiFi.h>
#include <WiFiMulti.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>

// ---------------- CONFIG — edit these ----------------
// Add/remove networks as needed. Use "" as the password for an open network.
struct WifiNet { const char* ssid; const char* password; };
const WifiNet WIFI_NETWORKS[] = {
  { "YOUR_WIFI_SSID",         "YOUR_WIFI_PASSWORD" },   // home
  { "YOUR_HOTSPOT_SSID",      "HOTSPOT_PASSWORD"   },   // phone hotspot
  { "GMI-Student",            ""                   },   // campus (open WiFi)
};

const char* SERVER_URL = "https://app.securevault.com.my/api/intrusion_alert.php";

// Must match BUZZER_DEVICE_KEY on the Pi (printed when you set it up).
const char* BUZZER_KEY = "CHANGE_ME_BUZZER_KEY";

const unsigned long POLL_INTERVAL_MS = 2000;
// -------------------------------------------------------

#define BUZZER_PIN 25
#define GREEN_LED_PIN 27  // physically a YELLOW LED on this build — lights up for SECURE/idle
#define RED_LED_PIN 26    // physically the RED LED — lights up for INTRUDER/alert
#define BUTTON_PIN 14

WiFiMulti wifiMulti;
WiFiClientSecure tlsClient;

bool alarmActive = false;
bool wasActive = false;
bool buzzOn = false;
int beepCountInGroup = 0;
bool inGroupPause = false;
unsigned long lastToggle = 0;
unsigned long lastPoll = 0;

bool lastButtonState = HIGH;
unsigned long lastButtonChange = 0;
const unsigned long DEBOUNCE_MS = 40;
bool buttonFired = false;

void sendAck();

// ---------- LED / idle vs alert display ----------

void showSecure() {
  digitalWrite(GREEN_LED_PIN, HIGH);
  digitalWrite(RED_LED_PIN, LOW);
}

void showIntruderBase() {
  digitalWrite(GREEN_LED_PIN, LOW);
  // RED_LED_PIN is toggled in sync with the buzzer in updateAlarm()
}

// ---------- Alarm state machine (non-blocking) ----------

void startAlarmLocal() {
  if (alarmActive) return;
  alarmActive = true;
  buzzOn = false;
  beepCountInGroup = 0;
  inGroupPause = false;
  lastToggle = millis();
  showIntruderBase();
}

void stopAlarmLocal() {
  alarmActive = false;
  digitalWrite(BUZZER_PIN, HIGH); // off (this buzzer module is active-LOW: LOW = on, HIGH = off)
  digitalWrite(RED_LED_PIN, LOW);
  showSecure();
}

void updateAlarm() {
  if (!alarmActive) return;
  unsigned long now = millis();

  if (inGroupPause) {
    if (now - lastToggle >= 200) {
      inGroupPause = false;
      beepCountInGroup = 0;
      lastToggle = now;
    }
    return;
  }

  unsigned long interval = buzzOn ? 80 : 60;
  if (now - lastToggle >= interval) {
    buzzOn = !buzzOn;
    digitalWrite(BUZZER_PIN, buzzOn ? LOW : HIGH); // active-LOW buzzer: LOW = on
    digitalWrite(RED_LED_PIN, buzzOn ? HIGH : LOW);
    lastToggle = now;

    if (!buzzOn) {
      beepCountInGroup++;
      if (beepCountInGroup >= 3) {
        inGroupPause = true;
        digitalWrite(RED_LED_PIN, LOW);
      }
    }
  }
}

// ---------- Physical stop button ----------

void checkButton() {
  bool reading = digitalRead(BUTTON_PIN);
  unsigned long now = millis();

  if (reading != lastButtonState) {
    lastButtonChange = now;
    lastButtonState = reading;
  }

  if ((now - lastButtonChange) > DEBOUNCE_MS) {
    if (reading == LOW && !buttonFired) {
      buttonFired = true;
      if (alarmActive) {
        stopAlarmLocal();
        sendAck(); // tell the server to clear the flag too
        wasActive = false;
      }
    } else if (reading == HIGH) {
      buttonFired = false;
    }
  }
}

// ---------- Server communication (HTTPS via Cloudflare) ----------

int httpGet(const char* action, String* bodyOut) {
  String url = String(SERVER_URL) + "?action=" + action;
  HTTPClient http;
  if (!http.begin(tlsClient, url)) {
    Serial.println("HTTPS begin failed");
    return -1;
  }
  http.setUserAgent("SecureVault-Buzzer/1.0");
  http.addHeader("X-Device-Key", BUZZER_KEY);
  http.setTimeout(6000);
  int code = http.GET();
  if (bodyOut && code == 200) *bodyOut = http.getString();
  http.end();
  return code;
}

bool pollStatus() {
  String body;
  int code = httpGet("status", &body);
  if (code != 200) {
    Serial.print("Status check failed, HTTP code: ");
    Serial.println(code);
    if (code == 401) Serial.println("  -> BUZZER_KEY does not match the server");
    return wasActive; // keep the current state on a temporary network error
  }
  return body.indexOf("\"active\":true") != -1;
}

void sendAck() {
  httpGet("ack", nullptr);
}

// ---------- WiFi ----------

void connectWiFi() {
  Serial.println("Connecting to WiFi (trying saved networks)...");
  while (wifiMulti.run(10000) != WL_CONNECTED) {
    Serial.println("  no saved network in range yet, retrying...");
    delay(1000);
  }
  Serial.print("WiFi connected to ");
  Serial.print(WiFi.SSID());
  Serial.print(", IP address: ");
  Serial.println(WiFi.localIP());
}

void setup() {
  Serial.begin(115200);

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, HIGH); // off (active-LOW buzzer module)

  pinMode(GREEN_LED_PIN, OUTPUT);
  pinMode(RED_LED_PIN, OUTPUT);
  showSecure();

  pinMode(BUTTON_PIN, INPUT_PULLUP);

  WiFi.mode(WIFI_STA);
  for (const WifiNet& n : WIFI_NETWORKS) {
    if (n.password[0] == '\0') wifiMulti.addAP(n.ssid);
    else                       wifiMulti.addAP(n.ssid, n.password);
  }

  // Encrypted HTTPS to Cloudflare. The certificate is not pinned (Cloudflare
  // rotates its edge certificates), so this protects against passive sniffing
  // but not an active man-in-the-middle on the local WiFi.
  tlsClient.setInsecure();

  connectWiFi();
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  checkButton();
  updateAlarm();

  unsigned long now = millis();
  if (now - lastPoll >= POLL_INTERVAL_MS) {
    lastPoll = now;
    bool active = pollStatus();

    if (active && !wasActive) {
      Serial.println(">>> ALERT STARTED");
      startAlarmLocal();
    } else if (!active && wasActive) {
      Serial.println(">>> Alert cleared — stopping alarm.");
      stopAlarmLocal();
    }
    wasActive = active;
  }
}
