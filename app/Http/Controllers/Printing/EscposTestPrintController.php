<?php

namespace App\Http\Controllers\Printing;

use App\Http\Controllers\Controller;
use App\Services\Printing\EscposUsbPrinter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mike42\Escpos\Printer;

class EscposTestPrintController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['nullable', 'string', 'max:200'],
        ]);

        $device = (string) config('escpos.device', '/dev/usb/lp1');
        $message = (string) ($request->input('message') ?? 'Hello Romeson Printer');
        $jobId = (string) Str::uuid();

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
            return response()->json([
                'ok' => false,
                'device' => $device,
                'error' => $e->getMessage(),
                'hint' => 'Ensure the PHP/web user can write to the device (e.g. add www-data to group lp).',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'device' => $device,
            'job_id' => $jobId,
        ]);
    }
}

