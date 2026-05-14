<?php

namespace App\Console\Commands;

use App\Services\Printing\EscposUsbPrinter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Mike42\Escpos\Printer;

class EscposTestPrint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'escpos:test
                            {message? : Optional message to print}
                            {--device= : Override device path (e.g. /dev/usb/lp1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test ESC/POS print to a USB device';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $device = (string) ($this->option('device') ?: config('escpos.device', '/dev/usb/lp1'));
        $message = (string) ($this->argument('message') ?: 'Hello Romeson Printer');
        $jobId = (string) Str::uuid();

        $this->info("Printing ESC/POS test slip to {$device} ...");

        try {
            $printer = new EscposUsbPrinter($device);
            $printer->run(function (Printer $p) use ($message, $jobId): void {
                $p->setEmphasis(true);
                $p->text("ESC/POS TEST PRINT\n");
                $p->setEmphasis(false);
                $p->text("Job: {$jobId}\n");
                $p->text("At: " . now()->format('Y-m-d H:i:s') . "\n");
                $p->feed();
                $p->text($message . "\n");
                $p->feed(3);
                $p->cut();
            });
        } catch (\Throwable $e) {
            $this->error('Print failed: ' . $e->getMessage());
            $this->line('Hint: ensure your PHP user has write permission (e.g. add www-data to group lp).');
            return Command::FAILURE;
        }

        $this->info("Done. Job id: {$jobId}");
        return Command::SUCCESS;
    }
}

