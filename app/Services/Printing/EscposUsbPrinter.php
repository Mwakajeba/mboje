<?php

namespace App\Services\Printing;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;

class EscposUsbPrinter
{
    public function __construct(
        private readonly string $devicePath,
    ) {
    }

    /**
     * @param  callable(Printer): void  $callback
     */
    public function run(callable $callback): void
    {
        $connector = null;
        $printer = null;

        try {
            $connector = new FilePrintConnector($this->devicePath);
            $printer = new Printer($connector);

            $callback($printer);
        } finally {
            if ($printer) {
                $printer->close();
            } elseif ($connector) {
                $connector->finalize();
            }
        }
    }
}

