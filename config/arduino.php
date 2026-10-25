<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Arduino Relay Controller Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for controlling the Arduino Mega 2560 / Uno relay module
    | connected via USB Serial COM Port to open the turnstile or door latch.
    |
    */

    'enabled' => env('ARDUINO_ENABLED', true),

    // COM Port on Windows (e.g. COM1, COM2, COM3, COM4, COM5...)
    'port' => env('ARDUINO_PORT', 'COM3'),

    // Baud rate (Default 9600 as commonly used in Arduino & Access)
    'baud' => env('ARDUINO_BAUD', 9600),

    // Character or string sent to Arduino to trigger relay (e.g. '1' or 'OPEN')
    'command' => env('ARDUINO_COMMAND', '1'),

    // Duration in milliseconds the relay stays energized (e.g. 3000 ms = 3 seconds)
    'pulse_ms' => env('ARDUINO_PULSE_MS', 3000),
];
