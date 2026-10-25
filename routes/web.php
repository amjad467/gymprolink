<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinancialAuditController;
use App\Http\Controllers\GateSimulatorController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MemberViewController;
use App\Http\Controllers\PosViewController;
use App\Http\Controllers\SalesAnalyticsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ==========================================
// چوونەژوورەوە و دەرچوون (Authentication & 2FA Routes)
// ==========================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/login/verify-2fa', [AuthController::class, 'show2fa'])->name('login.2fa');
Route::post('/login/verify-2fa', [AuthController::class, 'verify2fa'])->name('login.2fa.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==========================================
// بەشە پارێزراوەکانی سیستەم بە چوونەژوورەوە
// ==========================================
Route::middleware(['custom_auth'])->group(function () {

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // شیکاری و داشبۆردی کڕین و فرۆشتن (Sales & Inventory Analytics)
    Route::get('/analytics/sales', [SalesAnalyticsController::class, 'index'])->name('analytics.sales');
    Route::get('/analytics/sales/export', [SalesAnalyticsController::class, 'exportCsv'])->name('analytics.sales.export');

    // ڕێکخستنی سیستەم و هۆڵ و فۆنت (Settings, Logo, Fonts & Languages)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/theme/toggle', [SettingsController::class, 'toggleTheme'])->name('settings.theme.toggle');
    Route::get('/settings/locale/{locale}', [SettingsController::class, 'setLocale'])->name('settings.locale');

    // بەڕێوەبردنی باک ئەپی داتابەیس و گەڕاندنەوە (Backups & Restore)
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups/create', [BackupController::class, 'create'])->name('backups.create');
    Route::post('/backups/{filename}/restore', [BackupController::class, 'restore'])->name('backups.restore');
    Route::get('/backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::delete('/backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy');

    // تۆماری چاودێری و کردارە هەستیارەکان (Audit Trail Logs)
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // هاتنی ڕۆژانەی یاریزان و دەرگای زیرەک (Daily Attendance & Gate Control)
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/punch', [AttendanceController::class, 'punch'])->name('attendance.punch');
    Route::post('/attendance/gate/manual-open', [AttendanceController::class, 'manualOpenGate'])->name('attendance.gate.open');
    Route::post('/attendance/arduino/test', [AttendanceController::class, 'testArduino'])->name('attendance.arduino.test');
    Route::post('/attendance/{attendance}/checkout', [AttendanceController::class, 'manualCheckOut'])->name('attendance.checkout');

    // دەرگای زیرەک (Gate Simulator)
    Route::get('/gate/simulator', [AttendanceController::class, 'index'])->name('gate.simulator');
    Route::post('/gate/simulator/scan', [GateSimulatorController::class, 'processScan'])->name('gate.simulator.scan');

    // شۆپی تەواوکەری خۆراکی و POS
    Route::get('/pos', [PosViewController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [PosViewController::class, 'checkout'])->name('pos.checkout');
    Route::post('/pos/shift/open', [PosViewController::class, 'openShift'])->name('pos.shift.open');
    Route::post('/pos/shift/close', [PosViewController::class, 'closeShift'])->name('pos.shift.close');
    Route::get('/pos/invoices', [PosViewController::class, 'invoices'])->name('pos.invoices.index');
    Route::post('/pos/invoices/{sale}/refund', [PosViewController::class, 'refund'])->name('pos.invoices.refund');
    Route::get('/pos/invoices/{sale}/print', [PosViewController::class, 'printReceipt'])->name('pos.invoices.print');
    Route::get('/pos/invoices/{sale}/json', [PosViewController::class, 'invoiceJson'])->name('pos.invoices.json');
    Route::get('/pos/invoices/{sale}/whatsapp', [PosViewController::class, 'whatsappUrl'])->name('pos.invoices.whatsapp');

    // بەڕێوەبردنی کۆگا، ناساندنی کاڵاکان و کڕین لە دابینکەر
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/products', [InventoryController::class, 'storeProduct'])->name('inventory.products.store');
    Route::post('/inventory/batches', [InventoryController::class, 'storeBatch'])->name('inventory.batches.store');
    Route::post('/inventory/suppliers', [InventoryController::class, 'storeSupplier'])->name('inventory.suppliers.store');
    Route::post('/inventory/categories', [InventoryController::class, 'storeCategory'])->name('inventory.categories.store');
    Route::post('/inventory/brands', [InventoryController::class, 'storeBrand'])->name('inventory.brands.store');

    // یاریزانان، پرۆفایل، نوێکردنەوە، کارتی ئەندام، ڕاگرتن و ئەرشیف
    Route::get('/members', [MemberViewController::class, 'index'])->name('members.index');
    Route::get('/members/expired/export', [MemberViewController::class, 'exportExpired'])->name('members.expired.export');
    Route::post('/members', [MemberViewController::class, 'store'])->name('members.store');
    Route::get('/members/{member}', [MemberViewController::class, 'show'])->name('members.show');
    Route::get('/members/{member}/card', [MemberViewController::class, 'card'])->name('members.card');
    Route::post('/members/{member}/renew', [MemberViewController::class, 'renew'])->name('members.renew');
    Route::post('/members/{member}/freeze', [MemberViewController::class, 'freeze'])->name('members.freeze');
    Route::post('/members/{member}/unfreeze', [MemberViewController::class, 'unfreeze'])->name('members.unfreeze');
    Route::get('/members/{member}/whatsapp/receipt', [MemberViewController::class, 'whatsappReceipt'])->name('members.whatsapp.receipt');
    Route::get('/members/{member}/whatsapp/reminder', [MemberViewController::class, 'whatsappReminder'])->name('members.whatsapp.reminder');

    // بەڕێوەبردنی خەرجییە گشتییەکانی هۆڵ (Gym Expenses)
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    // ژماردن و داخستنی دارایی ڕۆژانەی سندوق (Daily Financial Audit / Z-Report)
    Route::get('/financial/daily-closing', [FinancialAuditController::class, 'dailyClosing'])->name('financial.closing');
    Route::get('/financial/daily-closing/print', [FinancialAuditController::class, 'printZReport'])->name('financial.closing.print');

    // بەڕێوەبردنی قەرزەکان، کەشف حیساب و دانەوەی قیست (Debt & Ledger Management)
    Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
    Route::post('/debts/{member}/pay', [DebtController::class, 'pay'])->name('debts.pay');
    Route::get('/debts/{member}/statement', [DebtController::class, 'statement'])->name('debts.statement');
    Route::get('/debts/{member}/whatsapp/reminder', [DebtController::class, 'whatsappReminder'])->name('debts.whatsapp.reminder');
    Route::get('/debts/payments/{payment}/print', [DebtController::class, 'printRepaymentReceipt'])->name('debts.payments.print');
    Route::get('/debts/payments/{payment}/whatsapp', [DebtController::class, 'whatsappRepaymentReceipt'])->name('debts.payments.whatsapp');

    // بەڕێوەبردنی بەکارهێنەران و مۆڵەتەکان (Users & Permissions Management)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
