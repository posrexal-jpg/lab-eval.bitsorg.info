<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PublicEvaluationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

/* ------------------------------------------------------------ public (QR scan) */
Route::prefix('e/{token}')->where(['token' => '[A-Za-z0-9]{32}'])->group(function () {
    Route::middleware('throttle:evaluation-view')->group(function () {
        Route::get('/', [PublicEvaluationController::class, 'show'])->name('evaluate.show');
        Route::get('/thanks', [PublicEvaluationController::class, 'thanks'])->name('evaluate.thanks');
    });
    Route::post('/', [PublicEvaluationController::class, 'store'])->middleware('throttle:evaluation-submit')->name('evaluate.store');
});

/* ------------------------------------------------------------------ auth */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/* ----------------------------------------------------------------- admin */
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/monitor', [Admin\DashboardController::class, 'monitor'])->name('monitor');

    // Rooms + QR
    Route::get('rooms/qr-print', [Admin\RoomController::class, 'qrPrint'])->name('rooms.qr-print');
    Route::get('rooms/{room}/qr', [Admin\RoomController::class, 'qr'])->name('rooms.qr');
    Route::get('rooms/{room}/qr.svg', [Admin\RoomController::class, 'qrDownload'])->name('rooms.qr.download');
    Route::post('rooms/{room}/regenerate', [Admin\RoomController::class, 'regenerate'])->middleware('throttle:admin-sensitive')->name('rooms.regenerate');
    Route::resource('rooms', Admin\RoomController::class)->except('show');

    // Form templates
    Route::get('templates/sample', [Admin\TemplateController::class, 'sample'])->name('templates.sample');
    Route::post('templates', [Admin\TemplateController::class, 'store'])->middleware('throttle:admin-upload')->name('templates.store');
    Route::post('templates/{template}/activate', [Admin\TemplateController::class, 'activate'])->name('templates.activate');
    Route::resource('templates', Admin\TemplateController::class)->only(['index', 'create', 'show', 'destroy']);

    // Paper form
    Route::get('paper-form', [Admin\PaperFormController::class, 'index'])->name('paper-form');
    Route::get('paper-form/print', [Admin\PaperFormController::class, 'print'])->name('paper-form.print');

    // Responses
    Route::get('evaluations', [Admin\EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('evaluations/print-form', [Admin\EvaluationController::class, 'printMany'])->name('evaluations.print-many');
    Route::get('evaluations/{evaluation}/form', [Admin\EvaluationController::class, 'printForm'])->name('evaluations.print-form');
    Route::get('evaluations/{evaluation}', [Admin\EvaluationController::class, 'show'])->name('evaluations.show');
    Route::patch('evaluations/{evaluation}/status', [Admin\EvaluationController::class, 'status'])->name('evaluations.status');
    Route::delete('evaluations/{evaluation}', [Admin\EvaluationController::class, 'destroy'])->name('evaluations.destroy');

    // Reports
    Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports');
    Route::get('reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');

    // Own account
    Route::get('account', [Admin\ProfileController::class, 'edit'])->name('profile');
    Route::put('account', [Admin\ProfileController::class, 'update'])->middleware('throttle:admin-sensitive')->name('profile.update');

    // Super admin only
    Route::middleware('super')->group(function () {
        Route::resource('admins', Admin\AdminUserController::class)->except('show');
        Route::get('audit', [Admin\AuditController::class, 'index'])->name('audit');
    });
});
