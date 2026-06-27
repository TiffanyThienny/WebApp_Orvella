@extends('layouts.dashboard')

@section('title', 'Manage Scans')

@section('dashboard_content')
<div x-data="{ scanModalOpen: false, selectedScan: null, patientData: {}, doctorData: {} }" class="space-y-8 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-4">
        <div class="flex-1">
            <div class="flex items-center space-x-4 mb-3">
                <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-2xl shadow-md shadow-indigo-500/20">
                    <i data-lucide="scan" class="w-7 h-7"></i>
                </div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">Scan History</h2>
            </div>
            <p class="text-slate-500 text-base max-w-2xl leading-relaxed ml-1">
                Track and manage all uploaded CT scans, their diagnostic status, and review results.
            </p>
        </div>
        <div class="flex items-center space-x-3 mt-2 md:mt-0">
            <a href="{{ route('medrec.upload') }}" class="px-6 py-3.5 text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 rounded-2xl shadow-lg shadow-blue-500/30 transition-all duration-300 flex items-center space-x-2 group transform hover:-translate-y-0.5">
                <i data-lucide="upload-cloud" class="w-5 h-5 group-hover:-translate-y-0.5 transition-transform"></i>
                <span>Upload New Scan</span>
            </a>
        </div>
    </div>

    <!-- Filters & Sort -->
    <form method="GET" action="{{ route('medrec.scans') }}" class="bg-white p-5 rounded-3xl border border-slate-200/60 shadow-sm flex flex-wrap items-end gap-5 transition-all duration-300 focus-within:shadow-md focus-within:border-indigo-200">
        <div class="flex-1 min-w-[200px] space-y-2.5">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status Filter</label>
            <div class="relative">
                <select name="status" onchange="this.form.submit()" class="w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-2xl text-sm font-semibold text-slate-700 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="uploaded" {{ request('status') == 'uploaded' ? 'selected' : '' }}>Uploaded</option>
                    <option value="ai_processing" {{ request('status') == 'ai_processing' ? 'selected' : '' }}>Processing</option>
                    <option value="pending_review" {{ request('status') == 'pending_review' ? 'selected' : '' }}>Pending Review</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <i data-lucide="chevron-down" class="absolute right-4 top-3.5 w-5 h-5 text-slate-400 pointer-events-none"></i>
            </div>
        </div>
        <div class="flex-1 min-w-[160px] space-y-2.5">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest">Start Date</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" onchange="this.form.submit()" class="w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-2xl text-sm font-semibold text-slate-700 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all cursor-pointer">
        </div>
        <div class="flex-1 min-w-[160px] space-y-2.5">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest">End Date</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" onchange="this.form.submit()" class="w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-2xl text-sm font-semibold text-slate-700 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all cursor-pointer">
        </div>
        <div class="flex-1 min-w-[160px] space-y-2.5">
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest">Sort By</label>
            <div class="relative">
                <select name="sort" onchange="this.form.submit()" class="w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-2xl text-sm font-semibold text-slate-700 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                </select>
                <i data-lucide="chevron-down" class="absolute right-4 top-3.5 w-5 h-5 text-slate-400 pointer-events-none"></i>
            </div>
        </div>
        @if(request('status') || request('start_date') || request('end_date') || request('sort') != 'newest')
            <div class="pb-1 pl-2">
                <a href="{{ route('medrec.scans') }}" class="px-5 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl text-sm font-bold transition-colors shadow-sm inline-flex items-center">
                    <i data-lucide="x" class="w-4 h-4 mr-1.5"></i> Clear Filters
                </a>
            </div>
        @endif
    </form>

    <!-- Scans Table -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden flex flex-col h-[calc(100vh-260px)] min-h-[500px]">
        <div class="flex-1 overflow-x-auto overflow-y-auto hide-scrollbar">
            <table class="w-full text-left whitespace-nowrap min-w-[900px]">
                <thead class="sticky top-0 z-10 bg-slate-50/80 backdrop-blur-sm shadow-sm">
                    <tr class="border-b border-slate-100 text-slate-500 text-[11px] uppercase tracking-widest font-extrabold">
                        <th class="px-7 py-5">Scan ID</th>
                        <th class="px-6 py-5">Medical Image</th>
                        <th class="px-6 py-5">Patient Info</th>
                        <th class="px-6 py-5">Assigned Doctor</th>
                        <th class="px-6 py-5">Status</th>
                        <th class="px-7 py-5 text-right">Upload Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    @forelse($scans as $scan)
                    @php
                        $patient = $patients->get($scan['patient_id']);
                        $doctor = isset($scan['doctor_id']) ? $doctors->get($scan['doctor_id']) : null;
                        $scanJson = json_encode([
                            'id' => $scan['id'],
                            'image_url' => $scan['image_url'] ?? null,
                            'analyzed_image_url' => $scan['ai_result']['analyzed_image_url'] ?? null,
                            'ai_prediction' => $scan['ai_result']['prediction_label'] ?? null,
                            'ai_confidence' => $scan['ai_result']['confidence'] ?? null,
                            'status' => $scan['status'] ?? 'uploaded',
                            'created_at' => $scan['created_at'] ?? null,
                            'patient_name' => $patient['user']['full_name'] ?? $patient['full_name'] ?? $patient['name'] ?? 'Unknown',
                            'patient_id' => $scan['patient_id'],
                            'doctor_name' => 'Dr. ' . ($doctor['full_name'] ?? 'Unknown')
                        ]);
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors duration-200 group cursor-pointer" @click="selectedScan = {{ $scanJson }}; scanModalOpen = true">
                        <td class="px-7 py-5 text-sm font-bold text-slate-700">
                            <span class="px-3 py-1.5 bg-slate-100/80 rounded-xl text-slate-600 font-mono text-xs border border-slate-200 shadow-sm">#{{ str_pad($scan['id'], 4, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-5">
                            <div class="w-16 h-16 rounded-2xl overflow-hidden border border-slate-200 bg-slate-900 flex items-center justify-center relative group-hover:border-indigo-300 group-hover:shadow-md transition-all cursor-pointer">
                                @if(!empty($scan['image_url']))
                                    <img src="{{ Str::startsWith($scan['image_url'], 'http') ? $scan['image_url'] : config('services.go_api.url', 'http://localhost:8080') . '/' . $scan['image_url'] }}" alt="Scan" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-[2px]">
                                        <i data-lucide="zoom-in" class="w-5 h-5 text-white drop-shadow-md"></i>
                                    </div>
                                @else
                                    <i data-lucide="image-off" class="w-6 h-6 text-slate-500"></i>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            @php
                                $patient = $patients->get($scan['patient_id']);
                            @endphp
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-700 flex items-center justify-center font-black text-sm shadow-inner shrink-0">
                                    {{ substr($patient['user']['full_name'] ?? $patient['full_name'] ?? $patient['name'] ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 text-sm group-hover:text-indigo-600 transition-colors mb-0.5">{{ $patient['user']['full_name'] ?? $patient['full_name'] ?? $patient['name'] ?? 'Unknown' }}</div>
                                    <div class="text-[11px] uppercase font-bold text-slate-400 tracking-wider flex items-center gap-1.5">
                                        <i data-lucide="fingerprint" class="w-3 h-3"></i> ID: {{ str_pad($scan['patient_id'] ?? 0, 4, '0', STR_PAD_LEFT) }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            @php
                                $doctor = isset($scan['doctor_id']) ? $doctors->get($scan['doctor_id']) : null;
                            @endphp
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-inner shrink-0 border border-blue-100">
                                    <i data-lucide="stethoscope" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 text-sm group-hover:text-blue-600 transition-colors mb-0.5">Dr. {{ $doctor['full_name'] ?? 'Unknown' }}</div>
                                    <div class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">{{ $doctor['specialty'] ?? 'Specialist' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            @php
                                $status = strtolower($scan['status'] ?? 'uploaded');
                            @endphp
                            
                            @if($status == 'uploaded')
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-2"></span> Uploaded
                                </span>
                            @elseif($status == 'ai_processing')
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <i data-lucide="loader-2" class="w-3.5 h-3.5 mr-1.5 animate-spin"></i> Processing
                                </span>
                            @elseif($status == 'pending_review')
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full mr-2 shadow-[0_0_8px_rgba(99,102,241,0.6)]"></span> Pending Review
                                </span>
                            @elseif($status == 'approved')
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span> Approved
                                </span>
                            @elseif($status == 'rejected')
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-100">
                                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full mr-2 shadow-[0_0_8px_rgba(244,63,94,0.8)]"></span> Rejected
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-2"></span> {{ $status }}
                                </span>
                            @endif
                        </td>
                        <td class="px-7 py-5 text-[11px] font-bold text-slate-500 text-right">
                            <span class="flex items-center justify-end uppercase tracking-wider">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 mr-1.5 text-slate-400"></i>
                                {{ \Carbon\Carbon::parse($scan['created_at'])->format('d M Y') }}
                                <span class="mx-1.5 text-slate-300">•</span>
                                {{ \Carbon\Carbon::parse($scan['created_at'])->format('H:i') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-24 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-5 border border-slate-100">
                                    <i data-lucide="inbox" class="w-10 h-10 text-slate-300"></i>
                                </div>
                                <h4 class="text-lg font-bold text-slate-700 mb-1">No Scans Found</h4>
                                <p class="text-sm font-medium text-slate-400 mb-5 max-w-sm">You haven't uploaded any CT scans yet or none match your filters.</p>
                                <a href="{{ route('medrec.upload') }}" class="px-5 py-3 bg-blue-50 text-blue-600 font-bold rounded-xl hover:bg-blue-100 transition-colors border border-blue-100 flex items-center shadow-sm">
                                    <i data-lucide="plus" class="w-4 h-4 mr-1.5"></i> Upload First Scan
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Footer -->
        @if(isset($pagination) && $pagination['total'] > 0)
        <div class="p-5 border-t border-slate-100 bg-white flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 font-bold uppercase tracking-wider gap-4">
            <span>Showing <span class="text-slate-800">{{ count($scans) }}</span> entries of <span class="text-slate-800">{{ $pagination['total'] }}</span> <span class="text-slate-400 lowercase">(Page {{ $pagination['page'] }} of {{ $pagination['total_pages'] }})</span></span>
            <div class="flex space-x-1.5">
                <a href="{{ request()->fullUrlWithQuery(['page' => max(1, $pagination['page'] - 1)]) }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 shadow-sm transition-colors {{ $pagination['page'] <= 1 ? 'opacity-50 pointer-events-none' : '' }}">Prev</a>
                
                @for($i = max(1, $pagination['page'] - 2); $i <= min($pagination['total_pages'], $pagination['page'] + 2); $i++)
                    <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}" class="px-4 py-2 rounded-xl border shadow-sm transition-colors {{ $i == $pagination['page'] ? 'bg-indigo-600 text-white border-indigo-600 shadow-indigo-500/20' : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-600' }}">{{ $i }}</a>
                @endfor
                
                <a href="{{ request()->fullUrlWithQuery(['page' => min($pagination['total_pages'], $pagination['page'] + 1)]) }}" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 shadow-sm transition-colors {{ $pagination['page'] >= $pagination['total_pages'] ? 'opacity-50 pointer-events-none' : '' }}">Next</a>
            </div>
        </div>
        @endif
    </div>
    <!-- Scan Detail Modal -->
    <div x-show="scanModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="scanModalOpen = false"></div>
        
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-4xl overflow-hidden relative z-10 flex flex-col max-h-[90vh]"
             x-transition:enter="transition ease-out duration-400"
             x-transition:enter-start="opacity-0 translate-y-12 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-12 sm:translate-y-0 sm:scale-95">
            
            <!-- Modal Header -->
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-white relative z-10">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-inner">
                        <i data-lucide="scan" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-xl tracking-tight">Scan Detail</h3>
                        <p class="text-xs text-slate-500 font-medium" x-text="'Scan ID: #' + String(selectedScan?.id).padStart(4, '0') + ' • Uploaded on ' + new Date(selectedScan?.created_at).toLocaleDateString()"></p>
                    </div>
                </div>
                <button type="button" @click="scanModalOpen = false" class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <!-- Modal Body: Side-by-side -->
            <div class="flex-1 overflow-y-auto hide-scrollbar p-8 bg-slate-50/30 flex flex-col md:flex-row gap-8">
                <!-- Left: CT Scan Image -->
                <div class="flex-1 space-y-4">
                    <div class="grid grid-cols-1 gap-4 h-full">
                        <!-- Original Image -->
                        <div class="space-y-3 h-full flex flex-col">
                            <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                                <i data-lucide="image" class="w-4 h-4"></i> Original CT Scan
                            </h4>
                            <div class="flex-1 min-h-[300px] bg-slate-950 rounded-2xl shadow-inner border border-slate-200/50 overflow-hidden relative flex items-center justify-center p-2">
                                <template x-if="selectedScan?.image_url">
                                    <img :src="(selectedScan.image_url.startsWith('http') ? '' : '{{ config('services.go_api.url', 'http://localhost:8080') }}/') + selectedScan.image_url" class="max-w-full max-h-full object-contain rounded-xl shadow-lg">
                                </template>
                                <template x-if="!selectedScan?.image_url">
                                    <div class="text-center text-slate-600">
                                        <i data-lucide="image-off" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
                                        <p class="text-xs font-bold uppercase tracking-wider">No Image Available</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Information & Diagnosis -->
                <div class="w-full md:w-80 space-y-6">
                    
                    <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm">
                        <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-4">Diagnostic Status</span>
                        
                        <div class="flex items-center space-x-2 mb-5">
                            <span class="px-4 py-2 text-xs font-black uppercase rounded-xl border tracking-widest flex items-center shadow-sm"
                                  :class="selectedScan?.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : (selectedScan?.status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-indigo-50 text-indigo-700 border-indigo-100')">
                                <span x-text="selectedScan?.status"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Meta Information -->
                    <div class="bg-white rounded-3xl p-6 border border-slate-200/60 shadow-sm space-y-5">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-widest border-b border-slate-100 pb-3 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-4 h-4 text-slate-400"></i> Record Details
                        </h4>
                        
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-inner shrink-0">
                                <i data-lucide="user" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Patient Name</p>
                                <p class="text-sm font-black text-slate-800" x-text="selectedScan?.patient_name"></p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-inner shrink-0">
                                <i data-lucide="stethoscope" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Assigned Doctor</p>
                                <p class="text-sm font-black text-slate-800" x-text="selectedScan?.doctor_name"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="px-8 py-5 border-t border-slate-100 bg-white flex justify-end">
                <button @click="scanModalOpen = false" class="px-6 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold rounded-xl transition-all duration-300 shadow-sm">Close Details</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        // Alpine data is inline inside x-data
    });
</script>
@endsection
