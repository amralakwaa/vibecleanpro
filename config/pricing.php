<?php

/*
 * Currency for every public price. The market is Saudi Arabia, so SAR is
 * the default; the amounts themselves are never configured here - they
 * are entered per service / per offer in the admin panel.
 */
return [
    'currency' => env('PRICING_CURRENCY', 'SAR'),
    'symbol' => env('PRICING_CURRENCY_SYMBOL', 'ر.س'),
];
