<?php

use App\Http\Controllers\BugTicketController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bug Ticket Routes
|--------------------------------------------------------------------------
|
| Wrap in auth middleware if you want to protect these routes.
| e.g. Route::middleware(['auth'])->group(function () { ... });
|
*/

Route::prefix('bug-tickets')->name('bug-tickets.')->group(function () {

    Route::get('/',          [BugTicketController::class, 'index'])   ->name('index');
    Route::get('/create',    [BugTicketController::class, 'create'])  ->name('create');
    Route::post('/',         [BugTicketController::class, 'store'])   ->name('store');
    Route::get('/{bugTicket}',          [BugTicketController::class, 'show'])    ->name('show');
    Route::get('/{bugTicket}/image',    [BugTicketController::class, 'image'])   ->name('image');
    Route::post('/{bugTicket}/approve', [BugTicketController::class, 'approve']) ->name('approve');
    Route::delete('/{bugTicket}',       [BugTicketController::class, 'destroy']) ->name('destroy');

});
