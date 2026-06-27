<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Illuminate\Http\Request;

class MedrecController extends Controller
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

        // Fetch doctors for name mapping
        $doctorsResponse = $this->api->get('/doctors');
        $doctors = collect($doctorsResponse->successful() ? ($doctorsResponse->json()['data'] ?? $doctorsResponse->json()) : [])->keyBy('id');

        // Filter for medrec dashboard requirements
        $rejectedScans = array_filter($scans, fn($s) => ($s['status'] ?? '') == 'rejected');
        $pendingScans = array_filter($scans, fn($s) => in_array($s['status'] ?? '', ['pending', 'analyzed', 'pending_review', 'uploaded', 'ai_processing']));
        $approvedScans = array_filter($scans, fn($s) => ($s['status'] ?? '') == 'approved');

        // Compute Today's Uploads
        $todayStr = \Carbon\Carbon::today()->toDateString();
        $todayUploadsCount = count(array_filter($scans, function($s) use ($todayStr) {
            return isset($s['created_at']) && substr($s['created_at'], 0, 10) === $todayStr;
        }));

        // Compute 7-day Trend Data
        $trendData = [];
        $trendLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();
            $trendLabels[] = $date->format('D');
            $trendData[] = count(array_filter($scans, function($s) use ($dateStr) {
                return isset($s['created_at']) && substr($s['created_at'], 0, 10) === $dateStr;
            }));
        }

        return view('medrec.dashboard', compact(
            'rejectedScans', 'pendingScans', 'approvedScans', 'doctors', 
            'todayUploadsCount', 'trendData', 'trendLabels'
        ));
    }

    public function uploadForm(Request $request)
    {
        $patientsResponse = $this->api->get('/patients');
        $patients = $patientsResponse->successful() ? ($patientsResponse->json()['data'] ?? $patientsResponse->json()) : [];
        
        $doctorsResponse = $this->api->get('/doctors');
        $doctors = $doctorsResponse->successful() ? ($doctorsResponse->json()['data'] ?? $doctorsResponse->json()) : [];

        $reuploadScan = null;
        if ($request->has('reupload')) {
            $scanId = $request->query('reupload');
            $scansResponse = $this->api->get('/scans');
            if ($scansResponse->successful()) {
                $scans = $scansResponse->json()['data'] ?? $scansResponse->json();
                $reuploadScan = collect($scans)->firstWhere('id', (int)$scanId);
            }
        }

        return view('medrec.upload', compact('patients', 'doctors', 'reuploadScan'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'patient_id' => 'required',
            'doctor_id' => 'required',
            'scan_image' => 'required|image|max:5120', // 5MB max
            'systolic' => 'required|numeric|min:50|max:250',
            'diastolic' => 'required|numeric|min:30|max:150',
            'heart_rate' => 'required|numeric|min:30|max:200',
            'weight' => 'required|numeric|min:10|max:300',
            'oxygen_level' => 'required|numeric|min:70|max:100',
            'temperature' => 'required|numeric|min:34|max:42',
            'notes' => 'required|string|min:5',
        ]);

        $isXhr = $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        // Step 1: Upload scan image
        $response = $this->api->upload('/scans', $request->file('scan_image'), [
            'patient_id' => $request->patient_id,
        ], 'image');

        if ($response->successful()) {
            $scanData = $response->json()['data'] ?? [];
            $scanID = $scanData['id'] ?? null;

            if ($scanID) {
                // Step 2: Assign Doctor
                $this->api->post("/scans/{$scanID}/assign-doctor", [
                    'doctor_id' => (int)$request->doctor_id,
                ]);

                // Step 3: Trigger AI Analysis
                $this->api->post("/scans/{$scanID}/analyze", []);

                // Step 4: Save Medical Vitals / Health Record
                $this->api->post('/health-records', [
                    'patient_id'   => (int)$request->patient_id,
                    'scan_id'      => (int)$scanID,
                    'systolic'     => (int)$request->systolic,
                    'diastolic'    => (int)$request->diastolic,
                    'heart_rate'   => (int)$request->heart_rate,
                    'weight'       => (float)$request->weight,
                    'oxygen_level' => (int)$request->oxygen_level,
                    'temperature'  => (float)$request->temperature,
                    'notes'        => $request->notes,
                ]);

                $redirectUrl = route('medrec.scans');
                if ($isXhr) {
                    return response()->json([
                        'success' => true,
                        'redirect' => $redirectUrl,
                        'message' => 'CT Scan uploaded and medical vitals saved successfully.'
                    ]);
                }
                return redirect()->route('medrec.scans')->with('success', 'CT Scan uploaded and medical vitals saved successfully.');
            }
        }

        $errorMsg = $response->json()['error'] ?? 'Failed to upload scan image.';
        if ($isXhr) {
            return response()->json(['success' => false, 'message' => 'Upload Failed: ' . $errorMsg], 422);
        }
        return back()->with('error', 'Upload Failed: ' . $errorMsg)->withInput();
    }

    public function scans(Request $request)
    {
        $page = (int)$request->query('page', 1);
        $limit = (int)$request->query('limit', 10);
        $status = $request->query('status', '');
        $sort = $request->query('sort', 'newest');
        $start_date = $request->query('start_date', '');
        $end_date = $request->query('end_date', '');

        $queryParams = [
            'page' => $page,
            'limit' => $limit,
            'status' => $status,
            'sort' => $sort,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        $response = $this->api->get('/scans', $queryParams);
        
        $responseData = $response->successful() ? $response->json() : [];
        $scans = $responseData['data'] ?? [];
        
        $pagination = [
            'total' => $responseData['total'] ?? 0,
            'page' => $responseData['page'] ?? 1,
            'limit' => $responseData['limit'] ?? 10,
            'total_pages' => $responseData['total_pages'] ?? 1,
        ];

        // Fetch patients and doctors for mapping names
        $patientsResponse = $this->api->get('/patients');
        $patients = collect($patientsResponse->successful() ? ($patientsResponse->json()['data'] ?? $patientsResponse->json()) : [])->keyBy('id');

        $doctorsResponse = $this->api->get('/doctors');
        $doctors = collect($doctorsResponse->successful() ? ($doctorsResponse->json()['data'] ?? $doctorsResponse->json()) : [])->keyBy('id');

        return view('medrec.scans', compact('scans', 'patients', 'doctors', 'pagination'));
    }

    public function patients()
    {
        $response = $this->api->get('/patients');
        $patients = $response->successful() ? ($response->json()['data'] ?? $response->json()) : [];
        return view('medrec.patients', compact('patients'));
    }
}
