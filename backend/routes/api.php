<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LedgerAccountController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\GuarantorController;
use App\Http\Controllers\LoanProductController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\DailyCollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ArrearsController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\LedgerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', function ($request, $next) {
    // Auto-update Arrears daily if late
    \App\Models\LoanSchedule::where('due_date', '<', now()->toDateString())
        ->whereIn('status', ['Pending', 'Partial'])
        ->update(['status' => 'Arrears']);

    return $next($request);
}])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [UserController::class, 'updateProfile']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{id}', [CustomerController::class, 'update']);

    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'store']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::apiResource('branches', BranchController::class);

    Route::apiResource('ledger-accounts', LedgerAccountController::class);
    Route::post('payments-receipts', [PaymentReceiptController::class, 'store']);
    Route::get('general-ledger', [PaymentReceiptController::class, 'getGeneralLedger']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    Route::get('/guarantors', [GuarantorController::class, 'index']);
    Route::post('/guarantors', [GuarantorController::class, 'store']);
    Route::delete('/guarantors/{id}', [GuarantorController::class, 'destroy']);

    Route::get('/loan-products', [LoanProductController::class, 'index']);
    Route::post('/loan-products', [LoanProductController::class, 'store']);
    Route::delete('/loan-products/{id}', [LoanProductController::class, 'destroy']);

    Route::get('/loans', [LoanController::class, 'index']);
    Route::post('/loans', [LoanController::class, 'store']);
    Route::put('/loans/{id}/approve', [LoanController::class, 'approve']);

    Route::get('/collections', [DailyCollectionController::class, 'index']);
    Route::post('/collections', [DailyCollectionController::class, 'store']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/chart', [DashboardController::class, 'chart']);
    Route::get('/notifications', [DashboardController::class, 'notifications']);

    Route::get('/arrears', [ArrearsController::class, 'index']);

    Route::get('/banks', [BankController::class, 'index']);
    Route::post('/banks', [BankController::class, 'store']);
    Route::delete('/banks/{id}', [BankController::class, 'destroy']);

    Route::get('/ledger/main', [LedgerController::class, 'mainLedger']);
    Route::get('/ledger/officer', [LedgerController::class, 'officerLedger']);
    Route::get('/ledger/bank', [LedgerController::class, 'bankLedger']);
    Route::post('/ledger/submit', [LedgerController::class, 'submitToMain']);
    Route::post('/ledger/deposit', [LedgerController::class, 'depositToBank']);

    // PDF Reports
    Route::get('/reports/daily-collection', [\App\Http\Controllers\ReportController::class, 'dailyCollection']);
    Route::get('/reports/arrears', [\App\Http\Controllers\ReportController::class, 'arrears']);
    Route::get('/reports/active-loans', [\App\Http\Controllers\ReportController::class, 'activeLoans']);
    Route::get('/reports/customer-statement/{id}', [\App\Http\Controllers\ReportController::class, 'customerStatement']);
});
