<?php

use App\Http\Controllers\CreditCardController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::call(function () {

//     (new CreditCardController())->createCreditCard((object)[
//         'lead_id' => 1,
//         'file_info_id' => 1,
//         'candidate_info_id' => 1,
//         'file_sl_no' => 'SL001',
//     ]);
// })->everySecond();
