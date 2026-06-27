<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    protected $api;

    public function __construct(GoApiService $api)
    {
        $this->api = $api;
    }

    public function dashboard()
    {
        $scansResponse = $this->api->get('/scans');
        $scans = $scansResponse->successful() ? ($scansResponse->json()['data'] ?? $scansResponse->json()) : [];

        // Scans waiting for this doctor's review (including uploaded, ai_processing)
        $pendingReviews = array_filter($scans, function($s) {
            return in_array(strtolower($s['status'] ?? ''), ['pending_review', 'uploaded', 'pending', 'ai_processing']);
        });
        
        // Strict FIFO (oldest first)
        usort($pendingReviews, function($a, $b) {
            return strtotime($a['created_at'] ?? 'now') <=> strtotime($b['created_at'] ?? 'now');
        });
        
        $doctorId = session('user')['id'] ?? null;

        // Calculate meaningful metrics
        $totalScansReviewed = count(array_filter($scans, fn($s) => ($s['status'] ?? '') == 'approved' && ($s['doctor_id'] ?? 0) == $doctorId));
        $pendingReviewsCount = count($pendingReviews);
        
        $statsResponse = $this->api->get('/analytics/doctor/stats');
        $statsData = $statsResponse->successful() ? ($statsResponse->json()['data'] ?? []) : [];
        $reviewedToday = $statsData['reviewed_today'] ?? 0;
        $avgReviewTimeMins = $statsData['avg_review_time_mins'] ?? 0;

        if ($avgReviewTimeMins <= 0) {
            $avgReviewTime = "0m";
        } elseif ($avgReviewTimeMins < 60) {
            $avgReviewTime = round($avgReviewTimeMins) . "m";
        } else {
            $avgReviewTime = round($avgReviewTimeMins / 60, 1) . "h";
        }

        // Calculate dynamic triage breakdown from all scans with AI results
        $clearCount = 0;
        $minorCount = 0;
        $criticalCount = 0;

        foreach ($scans as $s) {
            if (!empty($s['ai_result'])) {
                $risk = strtolower($s['ai_result']['risk_level'] ?? $s['ai_result']['prediction_label'] ?? '');
                if (str_contains($risk, 'tumor') || str_contains($risk, 'high') || str_contains($risk, 'critical')) {
                    $criticalCount++;
                } elseif (str_contains($risk, 'medium') || str_contains($risk, 'abnormal') || str_contains($risk, 'minor')) {
                    $minorCount++;
                } else {
                    $clearCount++;
                }
            }
        }

        // Calculate daily review activity for this doctor this week (Mon-Sun)
        $activityData = [0, 0, 0, 0, 0, 0, 0];
        $startOfWeek = \Carbon\Carbon::now()->startOfWeek();
        $endOfWeek = \Carbon\Carbon::now()->endOfWeek();

        foreach ($scans as $s) {
            $status = strtolower($s['status'] ?? '');
            if (in_array($status, ['approved', 'rejected']) && ($s['doctor_id'] ?? 0) == $doctorId) {
                $time = \Carbon\Carbon::parse($s['created_at']);
                if ($time->between($startOfWeek, $endOfWeek)) {
                    $dayIndex = $time->dayOfWeek === 0 ? 6 : $time->dayOfWeek - 1;
                    $activityData[$dayIndex]++;
                }
            }
        }

        $urgentApprovals = array_slice($pendingReviews, 0, 4); // Display up to 4 priority reviews

        // Dashboard Schedule Logic
        $schedulesResponse = $this->api->get("/schedules/{$doctorId}");
        $schedules = $schedulesResponse->successful() ? ($schedulesResponse->json()['data'] ?? []) : [];
        
        $todayStr = \Carbon\Carbon::now()->format('Y-m-d');
        $tomorrowStr = \Carbon\Carbon::now()->addDay()->format('Y-m-d');
        $todaySchedule = null;
        $tomorrowSchedule = null;
        
        foreach ($schedules as $s) {
            if (!$s['is_available']) continue;
            
            if (isset($s['appointment_date'])) {
                $start = substr($s['appointment_date'], 0, 10);
                $end = isset($s['end_date']) ? substr($s['end_date'], 0, 10) : $start;
                
                if ($todayStr >= $start && $todayStr <= $end) $todaySchedule = $s;
                if ($tomorrowStr >= $start && $tomorrowStr <= $end) $tomorrowSchedule = $s;
            } else {
                if ($s['day_of_week'] === \Carbon\Carbon::now()->format('l')) $todaySchedule = $s;
                if ($s['day_of_week'] === \Carbon\Carbon::now()->addDay()->format('l')) $tomorrowSchedule = $s;
            }
        }
        
        $activeSchedule = $todaySchedule;
        $scheduleTitle = "Today's Active Schedule";
        $scheduleDate = \Carbon\Carbon::now()->format('l, d M Y');
        
        if ($todaySchedule && $pendingReviewsCount === 0) {
            $activeSchedule = $tomorrowSchedule;
            $scheduleTitle = "Tomorrow's Schedule (Today Cleared!)";
            $scheduleDate = \Carbon\Carbon::now()->addDay()->format('l, d M Y');
        } elseif (!$todaySchedule && $tomorrowSchedule) {
            $activeSchedule = $tomorrowSchedule;
            $scheduleTitle = "Tomorrow's Schedule";
            $scheduleDate = \Carbon\Carbon::now()->addDay()->format('l, d M Y');
        } elseif (!$todaySchedule && !$tomorrowSchedule) {
            $scheduleTitle = "No Active Schedule";
            $scheduleDate = "";
        }

        return view('doctor.dashboard', compact(
            'pendingReviews', 'urgentApprovals', 'totalScansReviewed', 
            'pendingReviewsCount', 'avgReviewTime', 'activeSchedule', 
            'scheduleTitle', 'scheduleDate', 'clearCount', 'minorCount',
            'criticalCount', 'activityData', 'reviewedToday'
        ));
    }

    public function reviewQueue()
    {
        $scansResponse = $this->api->get('/scans');
        $scans = $scansResponse->successful() ? ($scansResponse->json()['data'] ?? $scansResponse->json()) : [];

        // Include pending scans AND scans approved/rejected by this specific doctor
        $doctorId = session('user')['id'] ?? null;
        $scans = array_filter($scans, function($s) use ($doctorId) {
            $status = strtolower($s['status'] ?? '');
            if (in_array($status, ['uploaded', 'ai_processing', 'pending_review', 'pending'])) {
                return true;
            }
            if (in_array($status, ['approved', 'rejected'])) {
                return ($s['doctor_id'] ?? 0) == $doctorId;
            }
            return false;
        });

        // Sort oldest first (FIFO)
        usort($scans, function($a, $b) {
            return strtotime($a['created_at'] ?? 'now') <=> strtotime($b['created_at'] ?? 'now');
        });

        return view('doctor.reviews.queue', compact('scans'));
    }

    public function patients()
    {
        $response = $this->api->get('/patients');
        $patients = $response->successful() ? ($response->json()['data'] ?? $response->json()) : [];
        return view('doctor.patients.index', compact('patients'));
    }

    public function showPatient($id)
    {
        $patientResponse = $this->api->get("/patients/{$id}");
        if ($patientResponse->successful()) {
            $payload = $patientResponse->json()['data'] ?? [];
            $patient = $payload['profile'] ?? [];
            // Go backend nests full_name inside user object
            $patient['full_name'] = $patient['user']['full_name']
                ?? $patient['full_name']
                ?? $patient['name']
                ?? 'Unknown Patient';
            $patient['scans'] = $payload['scans'] ?? [];
            $patient['records'] = $payload['records'] ?? [];
        } else {
            $patient = null;
        }

        $userId = $patient['user_id'] ?? null;
        $recordsResponse = $this->api->get("/health-records/graph", ['patient_user_id' => $userId]);
        $graphData = $recordsResponse->successful() ? ($recordsResponse->json()['data'] ?? []) : [];

        return view('doctor.patients.show', compact('patient', 'graphData'));
    }

    public function reviewScan($id)
    {
        $response = $this->api->get("/scans");
        $scans = $response->successful() ? ($response->json()['data'] ?? $response->json()) : [];
        $scan = collect($scans)->firstWhere('id', (int)$id);

        if (!$scan) {
            return redirect()->route('doctor.scans.queue')->with('error', 'Scan not found or not assigned to you.');
        }

        // Auto-run AI diagnostic analysis if result is empty and it's not currently processing/finalized
        $status = strtolower($scan['status'] ?? '');
        if (empty($scan['ai_result']) && !in_array($status, ['ai_processing', 'approved', 'rejected'])) {
            $this->api->post("/scans/{$id}/analyze", []);
            
            // Re-fetch scan data to get the updated status
            $response = $this->api->get("/scans");
            $scans = $response->successful() ? ($response->json()['data'] ?? $response->json()) : [];
            $scan = collect($scans)->firstWhere('id', (int)$id);
        }

        // Ensure ai_result exists to avoid null offset errors in view
        if (!isset($scan['ai_result'])) {
            $scan['ai_result'] = null;
        }

        return view('doctor.reviews.show', compact('scan'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|min:5',
            'food_allowed' => 'required|min:5',
            'food_avoided' => 'required|min:5',
            'recommended_activities' => 'required|min:5',
            'avoided_activities' => 'required|min:5',
            'lifestyle_recommendations' => 'required|min:5',
            'next_checkup' => 'required|min:3',
            'additional_notes' => 'required|min:5',
        ]);

        $comprehensiveNotes = json_encode([
            'medical_notes' => $request->notes,
            'food_allowed' => $request->food_allowed,
            'food_avoided' => $request->food_avoided,
            'recommended_activities' => $request->recommended_activities,
            'avoided_activities' => $request->avoided_activities,
            'lifestyle_recommendations' => $request->lifestyle_recommendations,
            'next_checkup' => $request->next_checkup,
            'additional_notes' => $request->additional_notes,
        ]);

        // Step 1: Submit diagnosis draft
        $diagResponse = $this->api->post('/diagnosis', [
            'scan_id' => (int)$id,
            'notes' => $comprehensiveNotes,
        ]);

        if ($diagResponse->successful()) {
            $diagId = $diagResponse->json()['data']['id'] ?? null;
            if ($diagId) {
                // Step 2: Approve diagnosis
                $approveResponse = $this->api->put("/diagnosis/{$diagId}/approve", [
                    'scan_id' => (int)$id,
                ]);

                if ($approveResponse->successful()) {
                    return redirect()->route('doctor.scans.queue')->with('success', 'Diagnosis approved successfully.');
                }
            }
        }

        return back()->with('error', 'Failed to approve diagnosis.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|min:5',
        ]);

        // Step 1: Create a diagnosis draft with the rejection reason
        $diagResponse = $this->api->post('/diagnosis', [
            'scan_id' => (int)$id,
            'notes'   => $request->rejection_reason,
        ]);

        if ($diagResponse->successful()) {
            $diagId = $diagResponse->json()['data']['id'] ?? null;
            if ($diagId) {
                // Step 2: Mark the diagnosis (and scan) as rejected
                $rejectResponse = $this->api->put("/diagnosis/{$diagId}/reject", [
                    'scan_id' => (int)$id,
                ]);

                if ($rejectResponse->successful()) {
                    return redirect()->route('doctor.scans.queue')->with('success', 'Scan rejected successfully.');
                }

                $error = $rejectResponse->json()['error'] ?? 'Failed to finalise rejection.';
                return back()->with('error', $error);
            }
        }

        $error = $diagResponse->json()['error'] ?? 'Failed to create rejection record.';
        return back()->with('error', $error);
    }

    public function analyzeScan($id)
    {
        $response = $this->api->post("/scans/{$id}/analyze", []);
        if ($response->successful()) {
            return back()->with('success', 'AI Analysis has been triggered successfully.');
        }
        return back()->with('error', 'Failed to trigger AI Analysis.');
    }

    public function history()
    {
        $scansResponse = $this->api->get('/scans');
        $scans = $scansResponse->successful() ? ($scansResponse->json()['data'] ?? $scansResponse->json()) : [];

        // Filter for completed/reviewed scans by this doctor
        $doctorId = session('user')['id'] ?? null;
        $historyScans = array_filter($scans, function($s) use ($doctorId) {
            $isDoctor = ($s['doctor_id'] ?? 0) == $doctorId;
            $isCompleted = in_array($s['status'] ?? '', ['approved', 'rejected']);
            return $isDoctor && $isCompleted;
        });

        // Sort descending by created_at or updated_at
        usort($historyScans, function($a, $b) {
            return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
        });

        return view('doctor.history', compact('historyScans'));
    }

    public function appointments()
    {
        $response = $this->api->get('/appointments');
        $appointments = $response->successful() ? ($response->json()['data'] ?? []) : [];

        // Normalize patient_name from Go's nested patient.user.full_name
        $appointments = array_map(function ($apt) {
            if (!isset($apt['patient_name']) || empty($apt['patient_name'])) {
                $apt['patient_name'] = $apt['patient']['user']['full_name']
                    ?? $apt['patient']['full_name']
                    ?? $apt['patient']['name']
                    ?? 'Unknown Patient';
            }
            return $apt;
        }, $appointments);

        $doctorId = session('user')['id'] ?? null;
        $schedulesResponse = $this->api->get("/schedules/{$doctorId}");
        $schedules = $schedulesResponse->successful() ? ($schedulesResponse->json()['data'] ?? []) : [];

        return view('doctor.appointments.index', compact('appointments', 'schedules'));
    }

    public function updateAppointmentStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected,completed',
        ]);

        $response = $this->api->put("/appointments/{$id}/status", [
            'status' => $request->status,
        ]);

        if ($response->successful()) {
            return back()->with('success', 'Appointment status updated successfully.');
        }

        return back()->with('error', 'Failed to update appointment status.');
    }
}
