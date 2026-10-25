<?php

namespace App\Console\Commands;

use App\Services\ArduinoRelayService;
use Illuminate\Console\Command;

class TestArduinoRelay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arduino:trigger {port? : پۆرتی COM بۆ نموونە COM3} {cmd? : فرمانەکە، وەک 1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ناردنی سیگناڵ بۆ ئاردینۆ و چرکاندنی ڕیلەی دەرگا (Arduino Relay Trigger)';

    /**
     * Execute the console command.
     */
    public function handle(ArduinoRelayService $arduinoService)
    {
        $port = $this->argument('port') ?? config('arduino.port', 'COM3');
        $cmd = $this->argument('cmd') ?? config('arduino.command', '1');

        $this->info("پەیوەستبوون بە پۆرتی [{$port}] و ناردنی فەرمانی [{$cmd}]...");

        $result = $arduinoService->triggerRelay($port, $cmd);

        if ($result['success']) {
            $this->info("✓ سەرکەوتوو: " . $result['message']);
            return Command::SUCCESS;
        } else {
            $this->error("✕ هەڵە: " . $result['message']);
            return Command::FAILURE;
        }
    }
}
