<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ESC/POS printing (USB device)
    |--------------------------------------------------------------------------
    |
    | For direct USB printing on Linux, point this to the character device
    | (e.g. /dev/usb/lp1). The web/PHP user must have write permission.
    |
    */
    'device' => env('ESCPOS_DEVICE', '/dev/usb/lp1'),
];

