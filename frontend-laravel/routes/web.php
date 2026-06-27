<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\MedrecController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (App\Services\GoApiService $api) {
    $response = $api->get('/configs');
    $configs = $response->successful() ? ($response->json()['data'] ?? []) : [];
    return view('welcome', compact('configs'));
})->name('home');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('forgot-password');
Route::post('/forgot-password', [AuthController::class, 'handleForgotPassword'])->name('forgot-password.post');

// Protected Routes — require active session
Route::middleware(['web', 'auth.session'])->group(function () {
    Route::get('/dashboard', function () {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $role = strtolower($user['role_name'] ?? $user['role'] ?? '');
        if ($role == 'admin') return redirect()->route('admin.dashboard');
        if ($role == 'doctor') return redirect()->route('doctor.dashboard');
        if ($role == 'medical record') return redirect()->route('medrec.dashboard');
        if ($role == 'patient') return redirect()->route('patient.dashboard');

        return view('dashboard');
    })->name('dashboard');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->middleware('auth.role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users/patients', [AdminController::class, 'patients'])->name('users.patients');
        Route::get('/users/doctors', [AdminController::class, 'doctors'])->name('users.doctors');
        Route::get('/users/medrec', [AdminController::class, 'medrec'])->name('users.medrec');
        Route::post('/users/create', [AdminController::class, 'createUser'])->name('users.create');
        Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');
        Route::get('/settings', [AdminController::class, 'accountSettings'])->name('settings');
        Route::put('/settings', [AdminController::class, 'updateAccount'])->name('settings.update');
        Route::get('/site-config', [AdminController::class, 'siteConfig'])->name('site-config');
        Route::post('/site-config', [AdminController::class, 'updateSiteConfig'])->name('site-config.update');
        Route::get('/schedules', [AdminController::class, 'schedules'])->name('schedules');
        Route::post('/schedules', [AdminController::class, 'assignSchedule'])->name('schedules.assign');
        Route::put('/schedules/{id}', [AdminController::class, 'updateSchedule'])->name('schedules.update');
        Route::get('/schedules/{doctor_id}', [AdminController::class, 'getDoctorSchedules'])->name('schedules.get');
        Route::delete('/schedules/{id}', [AdminController::class, 'deleteSchedule'])->name('schedules.delete');
    });

    // Doctor Routes
    Route::prefix('doctor')->name('doctor.')->middleware('auth.role:doctor')->group(function () {
        Route::get('/dashboard', [DoctorController::class, 'dashboard'])->name('dashboard');
        Route::get('/history', [DoctorController::class, 'history'])->name('history');
        Route::get('/patients', [DoctorController::class, 'patients'])->name('patients.index');
        Route::get('/patients/{id}', [DoctorController::class, 'showPatient'])->name('patients.show');
        Route::get('/scans/queue', [DoctorController::class, 'reviewQueue'])->name('scans.queue');
        Route::get('/scans/{id}/review', [DoctorController::class, 'reviewScan'])->name('scans.review');
        Route::put('/scans/{id}/approve', [DoctorController::class, 'approve'])->name('scans.approve');
        Route::put('/scans/{id}/reject', [DoctorController::class, 'reject'])->name('scans.reject');
        Route::post('/scans/{id}/analyze', [DoctorController::class, 'analyzeScan'])->name('scans.analyze');
        Route::get('/appointments', [DoctorController::class, 'appointments'])->name('appointments');
        Route::put('/appointments/{id}/status', [DoctorController::class, 'updateAppointmentStatus'])->name('appointments.status');
    });

    // Medrec Routes
    Route::prefix('medrec')->name('medrec.')->middleware('auth.role:medical record')->group(function () {
        Route::get('/dashboard', [MedrecController::class, 'dashboard'])->name('dashboard');
        Route::get('/upload', [MedrecController::class, 'uploadForm'])->name('upload');
        Route::post('/upload', [MedrecController::class, 'upload'])->name('upload.post');
        Route::get('/scans', [MedrecController::class, 'scans'])->name('scans');
        Route::get('/patients', [MedrecController::class, 'patients'])->name('patients');
    });

    // Patient Routes
    Route::prefix('patient')->name('patient.')->middleware('auth.role:patient')->group(function () {
        Route::get('/dashboard', [PatientController::class, 'dashboard'])->name('dashboard');
        Route::get('/appointments', [PatientController::class, 'appointments'])->name('appointments');
        Route::post('/appointments', [PatientController::class, 'bookAppointment'])->name('appointments.book');
        Route::put('/appointments/{id}/cancel', [PatientController::class, 'cancelAppointment'])->name('appointments.cancel');
        Route::get('/results', [PatientController::class, 'results'])->name('results');
        Route::get('/api/schedules/{doctor_id}', [PatientController::class, 'getDoctorSchedules'])->name('api.schedules');
    });
});
