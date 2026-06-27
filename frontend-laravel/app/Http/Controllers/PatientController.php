<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    protected $api;

    public function __construct(GoApiService $api)
    {
        $this->api = $api;
    }

    public function dashboard()
    {
        $user = session('user');
        $userId = $user['id'] ?? null;

        // Resolve patient record ID from user_id
        $patient = $this->resolvePatientByUserId($userId);

        // Scans come nested in the patient detail response
        $allScans = $patient['scans'] ?? [];
        
        // Patient's approved scans (for dashboard card)
        $approvedScans = array_filter($allScans, fn($s) => ($s['status'] ?? '') === 'approved');
        $latestApprovedScan = count($approvedScans) > 0 ? array_values($approvedScans)[0] : null;
        
        // Inject into patient array for dashboard use
        $patient['latest_approved_scan'] = $latestApprovedScan;
        $patient['scans'] = array_values($allScans);

        $appointmentsResponse = $this->api->get('/appointments');
        $appointments = $appointmentsResponse->successful()
            ? ($appointmentsResponse->json()['data'] ?? [])
            : [];

        return view('patient.dashboard', compact('patient', 'appointments'));
    }

    /**
     * Find the patient record for the currently logged-in user.
     * Uses /profile and /scans which are accessible to the Patient role.
     */
    private function resolvePatientByUserId($userId)
    {
        if (!$userId) return null;

        // /profile is accessible by all roles
        $profileResponse = $this->api->get('/profile');
        if (!$profileResponse->successful()) return null;
        $profileData = $profileResponse->json()['data'] ?? null;
        if (!$profileData) return null;

        // /scans returns only approved scans for Patient role (Go backend filters by role)
        $scansResponse = $this->api->get('/scans');
        $scans = $scansResponse->successful()
            ? ($scansResponse->json()['data'] ?? $scansResponse->json())
            : [];
        if (!is_array($scans)) $scans = [];

        // /health-records/graph is accessible by Patient role
        $recordsResponse = $this->api->get('/health-records/graph');
        $records = [];
        if ($recordsResponse->successful()) {
            $graphData = $recordsResponse->json();
            $records = $graphData['records'] ?? $graphData['data'] ?? [];
        }

        return [
            'full_name'       => $profileData['full_name'] ?? '',
            'id'              => $profileData['id'] ?? null,
            'user_id'         => $userId,
            'dob'             => $profileData['dob'] ?? '',
            'gender'          => $profileData['gender'] ?? '',
            'phone'           => $profileData['phone'] ?? '',
            'address'         => $profileData['address'] ?? '',
            'medical_history' => $profileData['medical_history'] ?? '',
            'allergies'       => $profileData['allergies'] ?? '',
            'scans'           => array_values($scans),
            'records'         => array_values($records),
        ];
    }

    public function results()
    {
        // /scans returns approved scans for Patient role directly
        $scansResponse = $this->api->get('/scans');
        $approvedResults = $scansResponse->successful()
            ? ($scansResponse->json()['data'] ?? $scansResponse->json())
            : [];
        if (!is_array($approvedResults)) $approvedResults = [];
        
        // Sort newest first
        usort($approvedResults, fn($a, $b) => strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now'));

        return view('patient.results', compact('approvedResults'));
    }

    public function appointments()
    {
        $doctorsResponse = $this->api->get('/doctors');
        $doctors = $doctorsResponse->successful()
            ? ($doctorsResponse->json()['data'] ?? $doctorsResponse->json())
            : [];

        $appointmentsResponse = $this->api->get('/appointments');
        $appointments = $appointmentsResponse->successful()
            ? ($appointmentsResponse->json()['data'] ?? [])
            : [];

        // Build slot map for doctors for the next 45 days from Cache
        $slotsMap = [];
        $today = \Carbon\Carbon::today();
        foreach ($doctors as $doc) {
            $docId = $doc['id'] ?? null;
            if ($docId) {
                $slotsMap[$docId] = [];
                for ($i = 0; $i < 45; $i++) {
                    $dateStr = $today->copy()->addDays($i)->format('Y-m-d');
                    $cacheKey = "doc_{$docId}_date_{$dateStr}";
                    $slotsMap[$docId][$dateStr] = (int)\Illuminate\Support\Facades\Cache::get($cacheKey, 0);
                }
            }
        }

        return view('patient.appointments', compact('doctors', 'appointments', 'slotsMap'));
    }

    public function bookAppointment(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $doctorId = (int)$request->doctor_id;
        $targetDate = $request->appointment_date;
        $targetTime = $request->appointment_time;

        // Validation 1: Prevent double booking for this patient on the same day
        $appointmentsResponse = $this->api->get('/appointments');
        $existingAppointments = $appointmentsResponse->successful() ? ($appointmentsResponse->json()['data'] ?? []) : [];
        
        foreach ($existingAppointments as $app) {
            $appDate = \Carbon\Carbon::parse($app['appointment_date'])->format('Y-m-d');
            if ($app['doctor_id'] == $doctorId && $appDate == $targetDate) {
                return back()->with('error', 'Double Booking Detected: You already have an appointment with this doctor on ' . \Carbon\Carbon::parse($targetDate)->format('d M Y') . '.');
            }
        }

        // Format date to RFC3339 / ISO 8601 for Go backend compatibility
        $isoDate = \Carbon\Carbon::parse($targetDate . ' ' . $targetTime)->format('Y-m-d\TH:i:s\Z');

        $originalDate = $targetDate;
        $wasMoved = false; // We removed auto-move logic since they pick specific time slots now.
        $cacheKey = "doc_{$doctorId}_date_{$targetDate}";

        $response = $this->api->post('/appointments', [
            'doctor_id' => $doctorId,
            'appointment_date' => $isoDate,
            'notes' => $request->notes ?? 'Daily Consultation Booking',
        ]);

        if ($response->successful()) {
            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                \Illuminate\Support\Facades\Cache::increment($cacheKey);
            } else {
                \Illuminate\Support\Facades\Cache::put($cacheKey, 1, now()->addDays(60));
            }
            
            // Get doctor name
            $doctorsResponse = $this->api->get('/doctors');
            $doctors = $doctorsResponse->successful() ? ($doctorsResponse->json()['data'] ?? $doctorsResponse->json()) : [];
            $doctorName = 'Specialist';
            foreach ($doctors as $d) {
                if (($d['id'] ?? null) == $doctorId) {
                    $doctorName = 'Dr. ' . ($d['full_name'] ?? $d['user']['full_name'] ?? $d['username'] ?? 'Specialist');
                    break;
                }
            }

            $msg = 'Appointment booked successfully for ' . \Carbon\Carbon::parse($targetDate)->format('d M Y') . '.';
            if ($wasMoved) {
                $msg = 'Quota Full for ' . \Carbon\Carbon::parse($originalDate)->format('d M Y') . '. Your appointment was automatically moved to the next available date: ' . \Carbon\Carbon::parse($targetDate)->format('d M Y') . '.';
            }

            return redirect()->route('patient.appointments')
                ->with('success', $msg)
                ->with('booked_doctor', $doctorName)
                ->with('booked_date', \Carbon\Carbon::parse($targetDate)->format('d M Y'))
                ->with('booked_time', \Carbon\Carbon::parse($targetDate . ' ' . $targetTime)->format('H:i'));
        }

        $errorMsg = $response->json()['error'] ?? 'Doctor might be overbooked or slot is unavailable.';
        return back()->with('error', 'Booking Failed: ' . $errorMsg);
    }

    public function getDoctorSchedules($doctor_id)
    {
        $response = $this->api->get("/schedules/{$doctor_id}");
        if ($response->successful()) {
            return response()->json($response->json());
        }
        return response()->json(['error' => 'Failed to fetch schedules'], 500);
    }

    public function cancelAppointment($id)
    {
        $response = $this->api->put("/appointments/{$id}/cancel");
        if ($response->successful()) {
            return back()->with('success', 'Consultation cancelled successfully.');
        }

        $error = $response->json()['error'] ?? 'Failed to cancel consultation.';
        return back()->with('error', $error);
    }
}
