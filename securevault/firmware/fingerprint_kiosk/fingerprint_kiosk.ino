/*
 * SecureVault - AS608 fingerprint kiosk (Arduino Uno/Nano + FPM library)
 *
 * Template disimpan dalam DATABASE, bukan dalam flash sensor.
 * Sensor cuma digunakan untuk scan + match (1:1).
 *
 * Wiring (sama macam contoh FPM):
 *   Arduino pin 2 (RX) <== AS608 TX
 *   Arduino pin 3 (TX) ==> AS608 RX
 *   AS608 VCC -> 3.3V atau 5V (ikut modul), GND -> GND
 *
 * Protokol USB Serial (115200 baud, satu baris per mesej, akhiri '\n'):
 *
 *   PC -> Arduino:
 *     PING
 *     ENROLL
 *     VERIFY <hex template>
 *
 *   Arduino -> PC:
 *     READY                      (lepas boot, sensor dijumpai)
 *     PONG
 *     STATUS PLACE_FINGER
 *     STATUS REMOVE_FINGER
 *     STATUS PLACE_AGAIN
 *     TEMPLATE <hex>             (hasil ENROLL, saiz ikut modul, contoh 512/768 byte)
 *     MATCH <score>              (hasil VERIFY, padan)
 *     NOMATCH                    (hasil VERIFY, tak padan)
 *     ERROR <sebab>
 */

#include <SoftwareSerial.h>
#include <fpm.h>

SoftwareSerial fserial(2, 3);
FPM finger(&fserial);
FPMSystemParams params;

#define HOST_BAUD        115200
#define SENSOR_BAUD      57600
#define TPL_MAX          768          // saiz template AS608 ini = 768 byte (6 paket x 128)
#define FINGER_TIMEOUT   15000UL      // ms tunggu user letak jari
#define HOST_TIMEOUT     3000UL       // ms maksimum jarak antara aksara dari PC

uint8_t tpl[TPL_MAX];
uint16_t tplLen = 0;
uint16_t packetLen = 128;

/* Stream kosong: buang data, untuk kira saiz kalau template > TPL_MAX */
class NullStream : public Stream {
  public:
    int available() { return 0; }
    int read() { return -1; }
    int peek() { return -1; }
    size_t write(uint8_t) { return 1; }
    size_t write(const uint8_t *, size_t n) { return n; }
};
NullStream nullStream;

bool readWord(char *out, uint8_t maxLen, char *term);
void skipLine();
uint16_t readHexTemplate();
void doEnroll();
void doVerify();

/* ------------------------------------------------------------------ */

void setup() {
    Serial.begin(HOST_BAUD);
    fserial.begin(SENSOR_BAUD);

    if (!finger.begin()) {
        Serial.println(F("ERROR SENSOR_NOT_FOUND"));
        while (1) yield();
    }
    finger.readParams(&params);
    packetLen = FPM::packetLengths[static_cast<uint8_t>(params.packetLen)];
    Serial.println(F("READY"));
}

void loop() {
    char cmd[12];
    char term;
    if (!readWord(cmd, sizeof(cmd), &term)) return;

    if (strcmp(cmd, "PING") == 0) {
        Serial.println(F("PONG"));
    }
    else if (strcmp(cmd, "ENROLL") == 0) {
        doEnroll();
    }
    else if (strcmp(cmd, "VERIFY") == 0 && term == ' ') {
        uint16_t n = readHexTemplate();
        if (n > 0 && n <= TPL_MAX) { tplLen = n; doVerify(); }
        else Serial.println(F("ERROR BAD_TEMPLATE_LENGTH"));
    }
    else if (cmd[0] != '\0') {
        Serial.println(F("ERROR UNKNOWN_COMMAND"));
    }

    if (term != '\n') skipLine();
}

/* ------------------------- Baca dari PC ---------------------------- */

int readCharTimeout() {
    unsigned long t = millis();
    while (!Serial.available()) {
        if (millis() - t > HOST_TIMEOUT) return -1;
        yield();
    }
    return Serial.read();
}

/* baca satu perkataan sehingga ' ' atau '\n'. Return false kalau tiada input. */
bool readWord(char *out, uint8_t maxLen, char *term) {
    if (!Serial.available()) return false;
    uint8_t i = 0;
    while (true) {
        int c = readCharTimeout();
        if (c < 0) { *term = '\n'; break; }
        if (c == '\r') continue;
        if (c == ' ' || c == '\n') { *term = (char)c; break; }
        if (i < maxLen - 1) out[i++] = (char)c;
    }
    out[i] = '\0';
    return true;
}

void skipLine() {
    int c;
    do { c = readCharTimeout(); } while (c >= 0 && c != '\n');
}

int8_t hexVal(char c) {
    if (c >= '0' && c <= '9') return c - '0';
    if (c >= 'a' && c <= 'f') return c - 'a' + 10;
    if (c >= 'A' && c <= 'F') return c - 'A' + 10;
    return -1;
}

/* baca hex terus ke tpl[] (tak guna String, jimat RAM Uno). Berhenti di '\n'. */
uint16_t readHexTemplate() {
    uint16_t n = 0;
    int8_t hi = -1;
    while (true) {
        int c = readCharTimeout();
        if (c < 0 || c == '\n') break;
        int8_t v = hexVal((char)c);
        if (v < 0) continue;
        if (hi < 0) hi = v;
        else {
            if (n < TPL_MAX) tpl[n] = (hi << 4) | v;
            n++;
            hi = -1;
        }
    }
    return n;
}

/* ------------------------- Sensor helpers -------------------------- */

bool waitFinger() {
    unsigned long t = millis();
    while (millis() - t < FINGER_TIMEOUT) {
        FPMStatus s = finger.getImage();
        if (s == FPMStatus::OK) return true;
        delay(50);
    }
    return false;
}

void waitRemove() {
    Serial.println(F("STATUS REMOVE_FINGER"));
    delay(500);
    unsigned long t = millis();
    while (finger.getImage() != FPMStatus::NOFINGER && millis() - t < FINGER_TIMEOUT) delay(100);
}

/* scan satu jari dan tukar ke template dalam buffer #slot */
bool captureTo(uint8_t slot) {
    if (!waitFinger()) { Serial.println(F("ERROR FINGER_TIMEOUT")); return false; }
    FPMStatus s = finger.image2Tz(slot);
    if (s != FPMStatus::OK) {
        Serial.print(F("ERROR IMAGE2TZ 0x")); Serial.println(static_cast<uint16_t>(s), HEX);
        return false;
    }
    return true;
}

void printHex(const uint8_t *buf, uint16_t len) {
    for (uint16_t i = 0; i < len; i++) {
        if (buf[i] < 0x10) Serial.print('0');
        Serial.print(buf[i], HEX);
    }
}

/* ---------------------------- ENROLL ------------------------------- */

void doEnroll() {
    Serial.println(F("STATUS PLACE_FINGER"));
    if (!captureTo(1)) return;
    waitRemove();

    Serial.println(F("STATUS PLACE_AGAIN"));
    if (!captureTo(2)) return;

    FPMStatus s = finger.generateTemplate();          // gabung buffer 1 + 2
    if (s == FPMStatus::ENROLLMISMATCH) { Serial.println(F("ERROR ENROLL_MISMATCH")); return; }
    if (s != FPMStatus::OK) { Serial.print(F("ERROR REGMODEL 0x")); Serial.println(static_cast<uint16_t>(s), HEX); return; }

    /* export template dari buffer 1 sensor -> Arduino (UpChar) */
    s = finger.downloadTemplate(1);
    if (s != FPMStatus::OK) { Serial.print(F("ERROR UPCHAR 0x")); Serial.println(static_cast<uint16_t>(s), HEX); return; }

    bool done = false;
    bool overflow = false;
    uint16_t pos = 0;
    while (!done) {
        uint16_t len;
        bool ok;
        if (TPL_MAX - pos >= packetLen) {
            len = TPL_MAX - pos;
            ok = finger.readDataPacket(tpl + pos, NULL, &len, &done);
        } else {
            /* buffer penuh: terus baca (buang) supaya dapat saiz sebenar */
            overflow = true;
            len = 0;
            ok = finger.readDataPacket(NULL, &nullStream, &len, &done);
        }
        if (!ok) { Serial.print(F("ERROR READ_TEMPLATE after ")); Serial.println(pos); return; }
        pos += len;
    }
    if (overflow) {
        Serial.print(F("ERROR TEMPLATE_TOO_BIG ")); Serial.println(pos);
        return;
    }
    tplLen = pos;

    Serial.print(F("TEMPLATE "));
    printHex(tpl, pos);
    Serial.println();
}

/* ---------------------------- VERIFY ------------------------------- */

void doVerify() {
    /* 1. scan jari live -> buffer 1 */
    Serial.println(F("STATUS PLACE_FINGER"));
    if (!captureTo(1)) return;

    /* 2. import template dari DB -> buffer 2 sensor (DownChar) */
    FPMStatus s = finger.uploadTemplate(2);
    if (s != FPMStatus::OK) { Serial.print(F("ERROR DOWNCHAR 0x")); Serial.println(static_cast<uint16_t>(s), HEX); return; }

    uint16_t written = 0;
    uint16_t len = tplLen;
    while (len) {
        if (!finger.writeDataPacket(tpl + written, NULL, &len, len <= packetLen)) {
            Serial.println(F("ERROR WRITE_TEMPLATE"));
            return;
        }
        written += len;
        len = tplLen - written;
    }

    /* 3. banding buffer 1 vs buffer 2 */
    uint16_t score = 0;
    s = finger.matchTemplatePair(&score);
    if (s == FPMStatus::OK) {
        Serial.print(F("MATCH ")); Serial.println(score);
    } else if (s == FPMStatus::NOMATCH) {
        Serial.println(F("NOMATCH"));
    } else {
        Serial.print(F("ERROR MATCH 0x")); Serial.println(static_cast<uint16_t>(s), HEX);
    }
}
