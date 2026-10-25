<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ArduinoRelayService
{
    /**
     * سیگناڵ دەنێرێت بۆ ئاردینۆ بە مەبەستی کردنەوەی دەرگا لە ڕێگەی پۆرتی Serial (COM)
     *
     * @param string|null $overridePort (ئەگەر پۆرتێکی دیاریکراو بەکاربهێنرێت بۆ نموونە COM3)
     * @param string|null $overrideCmd
     * @return array
     */
    public function triggerRelay(?string $overridePort = null, ?string $overrideCmd = null): array
    {
        if (!config('arduino.enabled', true)) {
            return [
                'success' => false,
                'message' => 'پەیوەندی بە ئاردینۆ ناچالاک کراوە لە ڕێکخستنەکاندا.',
            ];
        }

        $port = $overridePort ?? config('arduino.port', 'COM3');
        $baud = (int) config('arduino.baud', 9600);
        $command = $overrideCmd ?? config('arduino.command', '1');

        $cleanPort = strtoupper(trim($port));
        if (!str_starts_with($cleanPort, 'COM')) {
            $cleanPort = 'COM' . $cleanPort;
        }

        try {
            // ئەگەر سیستەمەکە لەسەر Windows بێت:
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // ڕێکخستنی خێرایی باود لە Windows بە کەرەستەی mode
                @exec("mode {$cleanPort}: BAUD={$baud} PARITY=N DATA=8 STOP=1 to=off 2>&1", $out, $returnVar);

                // کردنەوەی هێڵی Serial بە ناوی پێشکەوتووی کەرەستەکانی ویندۆز
                $devicePath = "\\\\.\\" . $cleanPort;
                $fp = @fopen($devicePath, 'w+b');
                if (!$fp) {
                    $fp = @fopen($cleanPort, 'w+b');
                }

                if (!$fp) {
                    $errMsg = "نەتوانرا پۆرتی {$cleanPort} بکرێتەوە؛ دڵنیابەرەوە لە بەستنی کەیبڵی USB ـی ئاردینۆ و داخستنی پۆرت لە پڕۆگرامەکانی تر.";
                    Log::warning("[ArduinoRelayService] " . $errMsg);
                    return [
                        'success' => false,
                        'port' => $cleanPort,
                        'message' => $errMsg,
                    ];
                }

                // ناردنی فرمانی کردنەوە بۆ ئاردینۆ
                fwrite($fp, $command . "\n");
                fflush($fp);
                fclose($fp);

                Log::info("[ArduinoRelayService] Command '{$command}' sent to {$cleanPort} successfully.");

                return [
                    'success' => true,
                    'port' => $cleanPort,
                    'message' => "دەرگا کرایەوە! سیگناڵ نێردرا بۆ ئاردینۆ لەسەر {$cleanPort}",
                ];
            } else {
                // لە سیستەمەکانی Linux یان macOS (/dev/ttyACM0 یان /dev/ttyUSB0)
                $linuxPort = '/dev/ttyACM0';
                if (!file_exists($linuxPort)) {
                    $linuxPort = '/dev/ttyUSB0';
                }

                if (file_exists($linuxPort)) {
                    @exec("stty -F {$linuxPort} {$baud} cs8 -cstopb -parenb");
                    $fp = @fopen($linuxPort, 'w+b');
                    if ($fp) {
                        fwrite($fp, $command . "\n");
                        fflush($fp);
                        fclose($fp);
                        return ['success' => true, 'port' => $linuxPort, 'message' => "سیگناڵ نێردرا بۆ {$linuxPort}"];
                    }
                }

                return [
                    'success' => false,
                    'message' => 'پۆرتی ئاردینۆ نەدۆزرایەوە لەسەر ژینگەی سیستەم.',
                ];
            }
        } catch (\Throwable $e) {
            Log::error("[ArduinoRelayService] هەڵە لە پەیوەندی بە ئاردینۆ: " . $e->getMessage());
            return [
                'success' => false,
                'port' => $cleanPort,
                'message' => 'هەڵەیەک ڕوویدا لە کاتی پەیوەندی بە ئاردینۆ: ' . $e->getMessage(),
            ];
        }
    }
}
