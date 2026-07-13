<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\SlaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - AMBATUGROW Support Center
|--------------------------------------------------------------------------
| Routes are grouped by feature area (name prefix) so blade views can
| reference them with route('area.action') the same way the sidebar
| navigation and cards on the dashboard do.
*/

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

// Ticket Management System
Route::name('tickets.')->prefix('tickets')->group(function () {
    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::get('/create', [TicketController::class, 'create'])->name('create');
    Route::post('/', [TicketController::class, 'store'])->name('store');
    Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
    Route::get('/{ticket}/edit', [TicketController::class, 'edit'])->name('edit');
    Route::patch('/{ticket}', [TicketController::class, 'update'])->name('update');
    Route::delete('/{ticket}', [TicketController::class, 'destroy'])->name('destroy');
});

// Self Service Portal
Route::get('/support', [SupportController::class, 'index'])->name('support.index');

// Customer Communication
Route::name('customers.')->prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
});

// SLA Tracking
Route::name('sla.')->prefix('sla')->group(function () {
    Route::get('/', [SlaController::class, 'index'])->name('index');
    Route::get('/data', [SlaController::class, 'data'])->name('data');
    Route::post('/rules', [SlaController::class, 'storeRule'])->name('rules.store');
    Route::put('/rules/{rule}', [SlaController::class, 'updateRule'])->name('rules.update');
    Route::delete('/rules/{rule}', [SlaController::class, 'destroyRule'])->name('rules.destroy');
    Route::get('/rules/{rule}/json', [SlaController::class, 'ruleJson'])->name('rules.json');
    Route::post('/export', [SlaController::class, 'export'])->name('export');
    Route::get('/export/download/{filename}', [SlaController::class, 'downloadExport'])->name('export.download');
});
