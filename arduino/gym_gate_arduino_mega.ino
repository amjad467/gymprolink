/*
 * =========================================================================
 * GymPro Laravel - Smart Gate Controller for Arduino Mega 2560 / Uno
 * سیستەمی کۆنترۆڵی دەرگای زیرەک و ڕیلەی ئامادەبووان بە ئاردینۆ
 * =========================================================================
 * 
 * ئەم کۆدە تایبەتە بە ئاردینۆ مێگا ٢٥٦٠ کە بە پۆرتی USB (COM) بەستراوە بە کۆمپیوتەر
 * کاتێک یاریزان کارت یان بارکۆد سکەن دەکات و بەشداریکردنی کارایە،
 * سیستەمی لارافڵ فەرمانی '1' یان "OPEN" دەنێرێت لە ڕێگەی Serial Port.
 * پاشان ئەم کۆدە ڕیلەیەکە چالاک دەکات بۆ ماوەی ٣ چرکە بۆ کردنەوەی دەرگا یان تەڕەنستایل.
 *
 * بەستنی وایەرەکان (Wiring Diagram):
 * -------------------------------------------------------------
 * 1. Relay Module VCC  -->  Arduino 5V
 * 2. Relay Module GND  -->  Arduino GND
 * 3. Relay Module IN   -->  Arduino Digital Pin 7 (یان هەر پینێکی دڵخواز)
 * 4. Relay COM & NO    -->  بەسترانی زنجیرەیی بە دەرگای کارەبایی (Magnetic Lock / Strike / Turnstile)
 * -------------------------------------------------------------
 */

const int RELAY_PIN = 7;          // پینی کۆنترۆڵکردنی ڕیلەی شینەکە
const int BUZZER_PIN = 8;         // پینی بیپ یان بازەر (ئارەزوومەندانە)
const int ONBOARD_LED = 13;       // گڵۆپی تاقیکردنەوەی سەر ئاردینۆ
const unsigned long PULSE_DURATION = 3000; // کاتی کراوە مانەوەی دەرگا بە میلی چرکە (٣ چرکە)

// دۆخی چالاکبوونی ڕیلەی (زۆربەی مۆدیوولەکانی ڕیلەی شین بە LOW چالاک دەبن: Active LOW)
const int RELAY_ACTIVE = LOW;    // ئەگەر ڕیلەیەکەت پێچەوانە بوو، بیکە بە HIGH
const int RELAY_INACTIVE = HIGH; // ئەگەر ڕیلەیەکەت پێچەوانە بوو، بیکە بە LOW

void setup() {
  // دەستپێکردنی پەیوەندی Serial بە خێرایی ٩٦٠٠ باود (هاوشێوەی ئەکسێس و لارافڵ)
  Serial.begin(9600);

  pinMode(RELAY_PIN, OUTPUT);
  pinMode(ONBOARD_LED, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);

  // دۆخی سەرەتایی: دەرگا داخراوە
  digitalWrite(RELAY_PIN, RELAY_INACTIVE);
  digitalWrite(ONBOARD_LED, LOW);
  digitalWrite(BUZZER_PIN, LOW);

  // بیپێکی کورت لە کاتی دەستپێکردن
  tone(BUZZER_PIN, 1200, 150);
  
  Serial.println("GYMPRO_GATE_ARDUINO_READY");
}

void loop() {
  // پشکنینی هاتنی فەرمان لە پۆرتی Serial (USB لە کۆمپیوتەرەوە)
  if (Serial.available() > 0) {
    char receivedChar = Serial.read();

    // فەرمانی '1' یان 'O' (OPEN) یان 'T' بۆ تاقیکردنەوە
    if (receivedChar == '1' || receivedChar == 'O' || receivedChar == 'o' || receivedChar == 'T') {
      openGate();
    }
    // فەرمانی پشکنینی پۆرت 'P' (Ping)
    else if (receivedChar == 'P' || receivedChar == 'p') {
      Serial.println("PONG_OK");
    }
  }
}

/**
 * فەنکشنی کردنەوەی دەرگا: چرکاندنی ڕیلەی و داخستنەوەی پاش ٣ چرکە
 */
void openGate() {
  Serial.println("GATE_OPENING");

  // چرکاندنی ڕیلەی و کردنەوەی دەرگا
  digitalWrite(RELAY_PIN, RELAY_ACTIVE);
  digitalWrite(ONBOARD_LED, HIGH);
  tone(BUZZER_PIN, 2000, 200); // دەنگی سەرکەوتن

  delay(PULSE_DURATION);

  // کوژاندنەوەی ڕیلەی و داخستنەوەی دەرگا
  digitalWrite(RELAY_PIN, RELAY_INACTIVE);
  digitalWrite(ONBOARD_LED, LOW);

  Serial.println("GATE_CLOSED");
}
