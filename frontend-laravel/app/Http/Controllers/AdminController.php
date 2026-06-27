<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $api;

    public function __construct(GoApiService $api)
    {
        $this->api = $api;
    }

    // ─── Dashboard ───────────────────────────────────────────
    public function dashboard()
    {
        $response = $this->api->get('/analytics/admin');
        $analytics = $response->successful() ? ($response->json()['data'] ?? $response->json()) : [];

        return view('admin.dashboard', compact('analytics'));
    }

    // ─── User List Pages ─────────────────────────────────────
    public function patients()
    {
        $response = $this->api->get('/users');
        $allUsers = $response->successful() ? ($response->json()['data'] ?? []) : [];
        $users = array_values(array_filter($allUsers, fn($u) => ($u['role_name'] ?? '') === 'Patient'));
        $doctors = array_values(array_filter($allUsers, fn($u) => ($u['role_name'] ?? '') === 'Doctor'));
        return view('admin.users.patients', compact('users', 'doctors'));
    }

    public function doctors()
    {
        $response = $this->api->get('/users');
        $allUsers = $response->successful() ? ($response->json()['data'] ?? []) : [];
        $users = array_values(array_filter($allUsers, fn($u) => ($u['role_name'] ?? '') === 'Doctor'));
        return view('admin.users.doctors', compact('users'));
    }

    public function medrec()
    {
        $response = $this->api->get('/users');
        $allUsers = $response->successful() ? ($response->json()['data'] ?? []) : [];
        $users = array_values(array_filter($allUsers, fn($u) => ($u['role_name'] ?? '') === 'Medical Record'));
        return view('admin.users.medrec', compact('users'));
    }

    // ─── Create User ─────────────────────────────────────────
    public function createUser(Request $request)
    {
        // Build validation rules
        $rules = [
            'username'  => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9._@]+$/',
            'email'     => 'required|email|max:100',
            'password'  => 'required|string|min:6|max:100',
            'full_name' => 'required|string|min:2|max:100',
            'role_id'   => 'required|integer|in:2,3,4',
            'phone'     => 'nullable|string|regex:/^[0-9]+$/|min:8|max:20',
            'address'   => 'nullable|string|max:255',
        ];

        $messages = [
            'username.regex'  => 'Username may only contain letters, numbers, dots, underscores, and @.',
            'username.min'    => 'Username must be at least 3 characters.',
            'phone.regex'     => 'Phone number must be only numbers.',
            'phone.min'       => 'Phone number must be at least 8 digits.',
            'password.min'    => 'Password must be at least 6 characters.',
            'full_name.min'   => 'Full name must be at least 2 characters.',
        ];

        // Doctor-specific validation
        if ((int) $request->role_id === 2) {
            $rules['specialty'] = 'required|string|max:100';
            $rules['phone']     = 'nullable|string|regex:/^[0-9]+$/|min:12|max:20';
            $messages['specialty.required'] = 'Specialty is required for doctors.';
            $messages['phone.min'] = 'Phone number must be at least 12 digits.';
        }

        // Patient-specific validation
        if ((int) $request->role_id === 3) {
            $rules['date_of_birth']     = 'required|date|before:today';
            $rules['gender']            = 'required|in:Male,Female';
            $rules['emergency_contact'] = 'required|string|regex:/^[0-9]+$/|min:8|max:20';
            $rules['allergies']         = 'nullable|string|max:500';
            $rules['medical_history']   = 'nullable|string|max:500';
            $messages['date_of_birth.before'] = 'Date of birth must be before today.';
            $messages['emergency_contact.regex'] = 'Emergency contact must be only numbers.';
        }

        $request->validate($rules, $messages);

        $payload = [
            'role_id'   => (int) $request->role_id,
            'username'  => $request->username,
            'email'     => $request->email,
            'password'  => $request->password,
            'full_name' => $request->full_name,
            'phone'     => $request->phone ?? '',
            'address'   => $request->address ?? '',
            'specialty' => $request->specialty ?? '',
        ];

        // Patient-specific fields
        if ((int)$request->role_id === 3) {
            $payload['date_of_birth']     = $request->date_of_birth;
            $payload['gender']            = $request->gender;
            $payload['emergency_contact'] = $request->emergency_contact;
            $payload['allergies']         = $request->allergies ?? '';
            $payload['medical_history']   = $request->medical_history ?? '';
        }

        $response = $this->api->post('/register', $payload);

        if ($response->successful()) {
            return back()->with('success', 'User successfully created.');
        }

        $error = $response->json()['message'] ?? $response->json()['error'] ?? 'Failed to create user.';
        return back()->with('error', $error)->withInput();
    }

    // ─── Update User ─────────────────────────────────────────
    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'full_name' => 'required|string|min:2|max:100',
            'username'  => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9._@]+$/',
            'email'     => 'required|email|max:100',
            'password'  => 'nullable|string|min:6|max:100',
            'phone'     => 'nullable|string|regex:/^[0-9]*$/|min:12|max:20',
            'address'   => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:100',
        ], [
            'full_name.min'   => 'Full name must be at least 2 characters.',
            'username.regex'  => 'Username may only contain letters, numbers, dots, underscores, and @.',
            'username.min'    => 'Username must be at least 3 characters.',
            'phone.regex'     => 'Phone number must be only numbers.',
            'phone.min'       => 'Phone number must be at least 12 digits.',
            'password.min'    => 'Password must be at least 6 characters.',
            'email.required'  => 'Email is required.',
            'email.email'     => 'Invalid email format.',
        ]);

        $payload = [
            'full_name' => $request->full_name,
            'username'  => $request->username,
            'email'     => $request->email,
            'phone'     => $request->phone ?? '',
            'address'   => $request->address ?? '',
            'specialty' => $request->specialty ?? '',
        ];

        // Only include password if provided
        if ($request->filled('password')) {
            $payload['password'] = $request->password;
        }

        $response = $this->api->put("/users/{$id}", $payload);

        if ($response->successful()) {
            return back()->with('success', 'User successfully updated.');
        }

        $error = $response->json()['message'] ?? $response->json()['error'] ?? 'Failed to update user.';
        return back()->with('error', $error);
    }

    // ─── Delete User ─────────────────────────────────────────
    public function deleteUser($id)
    {
        $response = $this->api->delete("/users/{$id}");

        if ($response->successful()) {
            return back()->with('success', 'User deleted successfully.');
        }

        $error = $response->json()['message'] ?? $response->json()['error'] ?? 'Failed to delete user.';
        return back()->with('error', $error);
    }

    // ─── Account Settings ────────────────────────────────────
    public function accountSettings()
    {
        $response = $this->api->get('/profile');
        $profile = $response->successful() ? ($response->json()['data'] ?? []) : [];
        return view('admin.settings', compact('profile'));
    }

    public function updateAccount(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|min:2|max:100',
            'username'  => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9._@]+$/',
            'phone'     => 'nullable|string|regex:/^[0-9]*$/|max:20',
            'address'   => 'nullable|string|max:255',
            'password'  => 'nullable|string|min:6|max:100',
        ], [
            'full_name.min' => 'Full name must be at least 2 characters.',
            'username.regex' => 'Username may only contain letters, numbers, dots, underscores, and @.',
            'username.min'  => 'Username must be at least 3 characters.',
            'phone.regex'   => 'Phone number must be only numbers.',
            'password.min'  => 'Password must be at least 6 characters.',
        ]);

        $payload = [
            'full_name' => $request->full_name,
            'username'  => $request->username,
            'phone'     => $request->phone ?? '',
            'address'   => $request->address ?? '',
        ];

        if ($request->filled('password')) {
            $payload['password'] = $request->password;
        }

        $response = $this->api->put('/profile', $payload);

        if ($response->successful()) {
            $user = session('user');
            $user['full_name'] = $request->full_name;
            $user['username'] = $request->username;
            session(['user' => $user]);
            return back()->with('success', 'Profile successfully updated.');
        }

        $error = $response->json()['message'] ?? $response->json()['error'] ?? 'Failed to update profile.';
        return back()->with('error', $error);
    }

    // ─── Site Config ─────────────────────────────────────────
    public function siteConfig()
    {
        $response = $this->api->get('/configs');
        // /configs is public, no auth header needed
        $configs = [];
        if ($response->successful()) {
            $configs = $response->json()['data'] ?? $response->json();
        }
        return view('admin.site-config', compact('configs'));
    }

    public function updateSiteConfig(Request $request)
    {
        $request->validate([
            'hero_title'      => 'nullable|string|max:200',
            'hero_subtitle'   => 'nullable|string|max:500',
            'hero_image'      => 'nullable|string|max:255',
            'stats_accuracy'  => 'nullable|string|max:20',
            'stats_doctors'   => 'nullable|string|max:20',
            'stats_patients'  => 'nullable|string|max:20',
            'stats_scans'     => 'nullable|string|max:20',
            'contact_email'   => 'nullable|email|max:100',
            'contact_phone'   => 'nullable|string|max:30',
            'contact_address' => 'nullable|string|max:500',
        ], [
            'contact_email.email' => 'Invalid email format.',
        ]);

        $payload = $request->except('_token');
        $response = $this->api->post('/configs', $payload);

        if ($response->successful()) {
            return back()->with('success', 'Site configuration successfully updated.');
        }

        return back()->with('error', 'Failed to update site configuration.');
    }

    // ─── Schedules ───────────────────────────────────────────
    public function schedules()
    {
        $response = $this->api->get('/users');
        $allUsers = $response->successful() ? ($response->json()['data'] ?? []) : [];
        $doctors = array_values(array_filter($allUsers, fn($u) => ($u['role_name'] ?? '') === 'Doctor'));

        $schedResponse = $this->api->get('/schedules');
        $schedules = $schedResponse->successful() ? ($schedResponse->json()['data'] ?? []) : [];

        // Calculate Stats
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        $activeSchedules = collect($schedules)->filter(fn($s) => $s['is_available'] ?? false);
        
        $stats = [
            'total_active' => $activeSchedules->count(),
            'doctors_today' => $activeSchedules->filter(function($s) use ($today) {
                $start = ($s['appointment_date'] ?? null) ? substr($s['appointment_date'], 0, 10) : '';
                $end = ($s['end_date'] ?? null) ? substr($s['end_date'], 0, 10) : $start;
                return $start && $start <= $today && $end >= $today;
            })->pluck('doctor_id')->unique()->count(),
            'total_slots' => $activeSchedules->sum('max_patients'),
            'total_doctors' => count($doctors),
        ];

        return view('admin.schedules', compact('doctors', 'schedules', 'stats'));
    }

    public function assignSchedule(Request $request)
    {
        $request->validate([
            'doctor_id'    => 'required|integer',
            'start_date'   => 'required|date|after_or_equal:today',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'max_patients' => 'required|integer|min:1|max:100',
            'is_available' => 'boolean'
        ]);

        $startDate = \Carbon\Carbon::parse($request->start_date);
        
        $payload = [[
            'doctor_id'        => (int) $request->doctor_id,
            'day_of_week'      => $startDate->format('l'),
            'appointment_date' => $request->start_date,
            'end_date'         => $request->end_date,
            'start_time'       => $request->start_time . ':00',
            'end_time'         => $request->end_time . ':00',
            'max_patients'     => (int) $request->max_patients,
            'is_available'     => $request->has('is_available')
        ]];

        $response = $this->api->post('/admin/schedules', $payload);

        if ($response->successful()) {
            return back()->with('success', 'Schedules assigned successfully for the selected date range.');
        }

        $error = $response->json()['message'] ?? $response->json()['error'] ?? 'Failed to assign schedules.';
        return back()->with('error', $error);
    }

    public function updateSchedule(Request $request, $id)
    {
        $request->validate([
            'doctor_id'    => 'required|integer',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'start_time'   => 'required',
            'end_time'     => 'required',
            'max_patients' => 'required|integer|min:1',
        ]);

        $startDate = \Carbon\Carbon::parse($request->start_date);

        // Safely append seconds only if not already present
        $startTime = strlen($request->start_time) === 5 ? $request->start_time . ':00' : $request->start_time;
        $endTime   = strlen($request->end_time)   === 5 ? $request->end_time   . ':00' : $request->end_time;

        $payload = [
            'doctor_id'        => (int) $request->doctor_id,
            'day_of_week'      => $startDate->format('l'),
            'appointment_date' => $request->start_date,
            'end_date'         => $request->end_date ?? $request->start_date,
            'start_time'       => $startTime,
            'end_time'         => $endTime,
            'max_patients'     => (int) $request->max_patients,
            'is_available'     => $request->has('is_available')
        ];

        $response = $this->api->put("/admin/schedules/{$id}", $payload);

        if ($response->successful()) {
            return back()->with('success', 'Schedule updated successfully.');
        }

        $error = $response->json()['message'] ?? $response->json()['error'] ?? 'Failed to update schedule.';
        return back()->with('error', $error)->withInput();
    }

    public function getDoctorSchedules($doctor_id)
    {
        $response = $this->api->get("/schedules/{$doctor_id}");
        if ($response->successful()) {
            return response()->json($response->json());
        }
        return response()->json(['error' => 'Failed to fetch schedules'], 500);
    }

    public function deleteSchedule($id)
    {
        $response = $this->api->delete("/admin/schedules/{$id}");
        if ($response->successful()) {
            return back()->with('success', 'Doctor schedule successfully deleted.');
        }
        $error = $response->json()['message'] ?? $response->json()['error'] ?? 'Failed to delete schedule.';
        return back()->with('error', $error);
    }
}
