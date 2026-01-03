<?php

use App\Http\Controllers\CardCaptureDataController;
use App\Http\Controllers\CreditCardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueueMonitorController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/createCreditCard', [CreditCardController::class, 'createCreditCard'])->name('api.createCreditCard');
    Route::get('/retry-queue/tbody', [CreditCardController::class, 'tbody'])->name('api.retry-queue.tbody');


    Route::get('/card_capture', [CardCaptureDataController::class, 'index'])->name('card_capture');



    Route::prefix('queue-monitor')->name('queue-monitor.')->group(function () {
        Route::get('/', [QueueMonitorController::class, 'index'])->name('index');
        Route::get('/metrics', [QueueMonitorController::class, 'metrics'])->name('metrics');
        Route::post('/retry/{id}', [QueueMonitorController::class, 'retryJob'])->name('retry');
        Route::post('/retry-all', [QueueMonitorController::class, 'retryAll'])->name('retry-all');
        Route::delete('/delete/{id}', [QueueMonitorController::class, 'deleteJob'])->name('delete');
        Route::delete('/delete-all', [QueueMonitorController::class, 'deleteAll'])->name('delete-all');
        Route::post('/restart-worker', [QueueMonitorController::class, 'restartWorker'])->name('restart-worker');
        Route::post('/run-worker', [QueueMonitorController::class, 'runWorker'])->name('run-worker');
        Route::post('/clear-queue', [QueueMonitorController::class, 'clearQueue'])->name('clear-queue');
    });

    Route::post('/queue-monitor/run-command', function (Request $request) {
        $validCommands = [
            'queue:work --stop-when-empty',
            'queue:flush',
            'queue:prune-failed',
            'queue:failed',
            'queue:retry all',
            'queue:forget',
        ];

        $command = $request->input('command');

        if (!in_array($command, $validCommands)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid command'
            ], 400);
        }

        try {
            Artisan::call($command);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => "Command executed: {$command}",
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    })->name('queue-monitor.run-command');
});


require __DIR__ . '/auth.php';
