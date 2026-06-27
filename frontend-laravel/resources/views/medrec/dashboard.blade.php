@extends('layouts.dashboard')

@section('title', 'Medrec Dashboard')

@section('dashboard_content')
<div class="space-y-6 max-w-7xl mx-auto" x-data="{ scanDetailOpen: false, selectedDashScan: null }">
    <!-- Rejection Alerts -->
    @if(count($rejectedScans) > 0)
    <div class="bg-red-50/80 backdrop-blur-md border border-red-200 p-5 rounded-2xl shadow-sm flex items-start space-x-4 animate-[pulse_3s_ease-in-out_infinite]">
        <div class="p-2 bg-red-100 rounded-xl">
            <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
        </div>
        <div class="flex-1 pt-1">
            <h3 class="text-base font-bold text-red-800">Action Required: {{ count($rejectedScans) }} Rejected Scans</h3>
            <p class="text-sm text-red-700 mt-1">Please review the rejection notes and re-upload the CT scans as requested by the doctors.</p>
        </div>
        <a href="#rejected-queue" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shadow-red-500/30">View Queue</a>
    </div>
    @endif

    <!-- Statistics Section -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute right-[-10px] top-[-10px] bg-blue-50 w-20 h-20 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm font-semibold text-slate-500 mb-1">Today's Uploads</p>
                    <p class="text-3xl font-black text-slate-800">{{ $todayUploadsCount }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                    <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute right-[-10px] top-[-10px] bg-cyan-50 w-20 h-20 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm font-semibold text-slate-500 mb-1">Awaiting Review</p>
                    <p class="text-3xl font-black text-slate-800">{{ count($pendingScans) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-cyan-100 text-cyan-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute right-[-10px] top-[-10px] bg-red-50 w-20 h-20 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm font-semibold text-slate-500 mb-1">Rejected Scans</p>
                    <p class="text-3xl font-black text-slate-800">{{ count($rejectedScans) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                </div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute right-[-10px] top-[-10px] bg-emerald-50 w-20 h-20 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm font-semibold text-slate-500 mb-1">Approved Scans</p>
                    <p class="text-3xl font-black text-slate-800">{{ count($approvedScans ?? []) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Daily Upload Trend</h3>
            <div id="uploadTrendChart" class="w-full h-[250px]"></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Processing Status</h3>
            <div id="processingChart" class="w-full h-[250px]"></div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        
        <!-- Pending Reviews -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col h-[500px]">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-10">
                <div class="flex items-center space-x-2">
                    <div class="p-1.5 bg-blue-100 text-blue-600 rounded-lg"><i data-lucide="clock" class="w-5 h-5"></i></div>
                    <h3 class="text-lg font-bold text-slate-800">Awaiting Doctor Review</h3>
                </div>
                <span class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-full border border-blue-200">{{ count($pendingScans) }} Pending</span>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
                <div class="space-y-2">
                    @forelse($pendingScans as $scan)
                    @php
                        $pendingDoctor = isset($scan['doctor_id']) ? $doctors->get($scan['doctor_id']) : null;
                        $pendingScanJson = json_encode([
                            'patient_name' => $scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'Unknown',
                            'doctor_name'  => $pendingDoctor ? 'Dr. ' . ($pendingDoctor['full_name'] ?? 'Unknown') : 'Not Assigned',
                            'doctor_specialty' => $pendingDoctor['specialty'] ?? 'Specialist',
                            'status'       => $scan['status'] ?? 'pending',
                            'ai_result'    => $scan['ai_result']['prediction_label'] ?? null,
                            'created_at'   => $scan['created_at'] ?? null,
                            'scan_id'      => $scan['id'] ?? null,
                        ]);
                    @endphp
                    <div class="p-4 rounded-xl border border-slate-100 hover:border-blue-200 hover:shadow-md hover:shadow-blue-500/5 transition-all bg-white group flex items-center justify-between cursor-pointer"
                         @click="selectedDashScan = {{ $pendingScanJson }}; scanDetailOpen = true">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-500">
                                {{ substr($scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'U', 0, 1) }}
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'Unknown' }}</h4>
                                    <div class="flex items-center space-x-2 text-xs text-slate-500 mt-1">
                                        <span class="flex items-center"><i data-lucide="calendar" class="w-3 h-3 mr-1"></i> {{ \Carbon\Carbon::parse($scan['created_at'])->format('d M, H:i') }}</span>
                                        <span>•</span>
                                        <span class="flex items-center text-blue-600 font-medium">
                                            <i data-lucide="clock" class="w-3 h-3 mr-1"></i> Awaiting Review
                                        </span>
                                    </div>
                                @if($pendingDoctor)
                                <div class="flex items-center text-[10px] text-slate-400 font-medium mt-0.5">
                                    <i data-lucide="stethoscope" class="w-3 h-3 mr-1 text-blue-400"></i>
                                    Dr. {{ $pendingDoctor['full_name'] ?? 'Unknown' }}
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $scan['status'] == 'analyzed' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $scan['status'] }}
                            </span>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 group-hover:text-blue-400 transition-colors"></i>
                        </div>
                    </div>
                    @empty
                    <div class="p-10 flex flex-col items-center justify-center text-slate-400">
                        <i data-lucide="inbox" class="w-12 h-12 mb-3 opacity-20"></i>
                        <p class="text-sm font-medium">No pending reviews found.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Rejected Queue -->
        <div id="rejected-queue" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col h-[500px]">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-10">
                <div class="flex items-center space-x-2">
                    <div class="p-1.5 bg-red-100 text-red-600 rounded-lg"><i data-lucide="x-circle" class="w-5 h-5"></i></div>
                    <h3 class="text-lg font-bold text-slate-800">Rejected Scan Queue</h3>
                </div>
                <span class="px-3 py-1 bg-red-50 text-red-700 text-xs font-bold rounded-full border border-red-200 uppercase tracking-wider">Urgent</span>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
                <div class="space-y-3">
                    @forelse($rejectedScans as $scan)
                    @php
                        $rejDoctor = isset($scan['doctor_id']) ? $doctors->get($scan['doctor_id']) : null;
                        $rejScanJson = json_encode([
                            'patient_name'     => $scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'Unknown',
                            'doctor_name'      => $rejDoctor ? 'Dr. ' . ($rejDoctor['full_name'] ?? 'Unknown') : 'Not Assigned',
                            'doctor_specialty' => $rejDoctor['specialty'] ?? 'Specialist',
                            'status'           => 'rejected',
                            'notes'            => $scan['diagnosis']['notes'] ?? 'No rejection notes provided.',
                            'created_at'       => $scan['created_at'] ?? null,
                            'scan_id'          => $scan['id'] ?? null,
                            'reupload_url'     => route('medrec.upload', ['reupload' => $scan['id']]),
                        ]);
                    @endphp
                    <div class="p-5 rounded-xl border border-red-100 hover:border-red-300 hover:shadow-md hover:shadow-red-500/10 transition-all bg-red-50/30 group cursor-pointer"
                         @click="selectedDashScan = {{ $rejScanJson }}; scanDetailOpen = true">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-red-100 border border-red-200 flex items-center justify-center font-bold text-red-600">
                                    {{ substr($scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900">{{ $scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'Unknown Patient' }}</h4>
                                    <p class="text-xs text-slate-500 flex items-center mt-0.5"><i data-lucide="clock" class="w-3 h-3 mr-1"></i> {{ \Carbon\Carbon::parse($scan['created_at'])->diffForHumans() }}</p>
                                    @if($rejDoctor)
                                    <p class="text-[10px] text-red-500 font-bold flex items-center mt-0.5">
                                        <i data-lucide="stethoscope" class="w-3 h-3 mr-1"></i>
                                        Dr. {{ $rejDoctor['full_name'] ?? 'Unknown' }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('medrec.upload', ['reupload' => $scan['id']]) }}" @click.stop class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white text-xs font-bold rounded-lg hover:shadow-lg hover:shadow-red-500/30 transition-all flex items-center space-x-1">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                    <span>Re-upload</span>
                                </a>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-red-300 group-hover:text-red-500 transition-colors"></i>
                            </div>
                        </div>
                        <div class="p-3.5 bg-white rounded-lg border border-red-100 shadow-sm relative">
                            <div class="absolute -left-1.5 top-4 w-3 h-3 bg-white border-t border-l border-red-100 transform -rotate-45"></div>
                            <p class="text-[10px] font-bold text-red-400 uppercase tracking-widest mb-1.5 flex items-center"><i data-lucide="stethoscope" class="w-3 h-3 mr-1"></i> Doctor's Note</p>
                            <p class="text-sm text-slate-700 italic">"{{ $scan['diagnosis']['notes'] ?? 'No rejection notes provided.' }}"</p>
                        </div>
                    </div>
                    @empty
                    <div class="p-10 flex flex-col items-center justify-center text-slate-400 h-full">
                        <i data-lucide="check-circle-2" class="w-12 h-12 mb-3 text-emerald-400 opacity-50"></i>
                        <p class="text-sm font-medium">Clear! No rejected scans in queue.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <!-- Scan Detail Modal (Dashboard) -->
    <div x-show="scanDetailOpen"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="scanDetailOpen = false"></div>

        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md relative z-10 overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between"
                 :class="selectedDashScan?.status === 'rejected' ? 'bg-red-50' : 'bg-blue-50'">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         :class="selectedDashScan?.status === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'">
                        <i data-lucide="file-heart" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-base"
                            x-text="selectedDashScan?.status === 'rejected' ? 'Rejected Scan Detail' : 'Awaiting Review Detail'"></h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider"
                           x-text="'Scan #' + String(selectedDashScan?.scan_id || 0).padStart(4,'0')"></p>
                    </div>
                </div>
                <button @click="scanDetailOpen = false" class="w-8 h-8 rounded-xl bg-white/70 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4">
                <!-- Patient -->
                <div class="flex items-center space-x-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-black text-base shrink-0"
                         x-text="(selectedDashScan?.patient_name || 'U').charAt(0)"></div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Patient</p>
                        <p class="font-black text-slate-800" x-text="selectedDashScan?.patient_name"></p>
                    </div>
                </div>

                <!-- Doctor -->
                <div class="flex items-center space-x-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                        <i data-lucide="stethoscope" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Assigned Doctor</p>
                        <p class="font-black text-slate-800" x-text="selectedDashScan?.doctor_name"></p>
                        <p class="text-xs text-slate-500" x-text="selectedDashScan?.doctor_specialty"></p>
                    </div>
                </div>

                <!-- Status -->
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</p>
                        <span class="px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider"
                              :class="selectedDashScan?.status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'"
                              x-text="selectedDashScan?.status"></span>
                    </div>
                </div>

                <!-- Rejection Notes (only for rejected) -->
                <div x-show="selectedDashScan?.status === 'rejected' && selectedDashScan?.notes"
                     class="p-4 bg-red-50 rounded-2xl border border-red-100">
                    <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest mb-2 flex items-center">
                        <i data-lucide="message-square" class="w-3 h-3 mr-1"></i> Doctor's Rejection Note
                    </p>
                    <p class="text-sm text-slate-700 italic" x-text="'"' + (selectedDashScan?.notes || '') + '"'"></p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-100 flex justify-between items-center bg-slate-50/50">
                <button @click="scanDetailOpen = false" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-600 text-sm font-bold rounded-xl hover:bg-slate-100 transition-colors shadow-sm">Close</button>
                <a x-show="selectedDashScan?.status === 'rejected' && selectedDashScan?.reupload_url"
                   :href="selectedDashScan?.reupload_url"
                   class="px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white text-sm font-bold rounded-xl hover:shadow-lg hover:shadow-red-500/30 transition-all flex items-center space-x-2">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>Re-upload Scan</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Trend Chart
    var optionsTrend = {
        series: [{ name: 'Uploads', data: @json($trendData) }],
        chart: { type: 'bar', height: 250, fontFamily: 'Inter, sans-serif', toolbar: { show: false } },
        colors: ['#0EA5E9'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '40%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: @json($trendLabels), axisBorder: { show: true, color: '#cbd5e1' }, axisTicks: { show: true, color: '#cbd5e1' } },
        yaxis: { show: true, axisBorder: { show: true, color: '#cbd5e1' }, axisTicks: { show: true, color: '#cbd5e1' } },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
    };
    new ApexCharts(document.querySelector("#uploadTrendChart"), optionsTrend).render();

    // Processing Chart
    var optionsProcess = {
        series: [{{ count($pendingScans) }}, {{ count($approvedScans) }}, {{ count($rejectedScans) }}],
        labels: ['Pending', 'Approved', 'Rejected'],
        chart: { type: 'radialBar', height: 250, fontFamily: 'Inter, sans-serif' },
        colors: ['#0EA5E9', '#10B981', '#EF4444'],
        plotOptions: {
            radialBar: {
                hollow: { size: '40%' },
                dataLabels: {
                    name: { fontSize: '12px' },
                    value: { fontSize: '16px', fontWeight: 600, color: '#334155' }
                }
            }
        },
        stroke: { lineCap: 'round' }
    };
    new ApexCharts(document.querySelector("#processingChart"), optionsProcess).render();
});
</script>
@endsection
