@extends('layouts.dashboard')

@section('title', 'Patient Health Overview')

@section('dashboard_content')
@php
    $latestRecord = collect($patient['records'] ?? [])->first();
    $recordsHistory = collect($patient['records'] ?? [])->reverse();
    $scoresHistory = $recordsHistory->pluck('health_score')->toArray();
    $datesHistory = $recordsHistory->map(fn($r) => \Carbon\Carbon::parse($r['created_at'])->format('d M'))->toArray();
@endphp
<div class="space-y-6 max-w-6xl mx-auto">
    <!-- Welcome Card (Glassmorphism) -->
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 rounded-2xl shadow-xl overflow-hidden relative text-white">
        <!-- Floating Elements -->
        <div class="absolute inset-0 z-0 pointer-events-none">
            <div class="absolute top-[-20%] right-[-10%] w-[50%] h-[150%] bg-white/10 rotate-12 transform blur-3xl rounded-full"></div>
            <i data-lucide="heart-pulse" class="absolute bottom-4 right-10 w-48 h-48 text-white/5 transform -rotate-12"></i>
        </div>
        
        <div class="p-8 md:p-10 relative z-10 flex flex-col md:flex-row md:items-center justify-between">
            <div class="max-w-2xl">
                <h2 class="text-3xl md:text-4xl font-extrabold mb-2 tracking-tight">Hello, {{ explode(' ', $patient['full_name'] ?? 'Guest')[0] }}!</h2>
                <p class="text-blue-100 text-lg mb-6">Your health is our priority. Your latest medical summary is looking great. Keep up the good work!</p>
                
                <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                    <a href="{{ route('patient.appointments') }}" class="inline-flex items-center justify-center px-6 py-3 bg-white text-blue-600 font-bold rounded-xl shadow-lg hover:bg-slate-50 hover:shadow-xl transition-all transform hover:-translate-y-0.5">
                        <i data-lucide="calendar-plus" class="w-5 h-5 mr-2"></i> Book Appointment
                    </a>
                    <a href="{{ route('patient.results') }}" class="inline-flex items-center justify-center px-6 py-3 bg-white/20 backdrop-blur-md border border-white/30 text-white font-bold rounded-xl hover:bg-white/30 transition-all">
                        <i data-lucide="file-text" class="w-5 h-5 mr-2"></i> View Diagnosis
                    </a>
                </div>
            </div>
            
            <div class="hidden md:block">
                <div class="w-32 h-32 rounded-full border-4 border-white/20 flex items-center justify-center backdrop-blur-sm relative">
                    <div class="absolute inset-0 rounded-full border-4 border-white border-t-transparent animate-spin" style="animation-duration: 3s;"></div>
                    <div class="text-center">
                        <span class="block text-3xl font-black">{{ $latestRecord ? $latestRecord['health_score'] : '--' }}</span>
                        <span class="block text-[10px] uppercase tracking-widest text-blue-100 font-semibold">Health Score</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Upcoming Appointments (Sidebar) -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col h-full">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 sticky top-0 z-10">
                <div class="flex items-center space-x-2">
                    <div class="p-1.5 bg-indigo-100 text-indigo-600 rounded-lg"><i data-lucide="calendar-clock" class="w-5 h-5"></i></div>
                    <h3 class="font-bold text-slate-800 text-[15px]">Upcoming Visits</h3>
                </div>
                <a href="{{ route('patient.appointments') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 uppercase tracking-wide">See All</a>
            </div>
            <div class="flex-1 p-5 space-y-4 overflow-y-auto custom-scrollbar">
                @php
                    $upcoming = array_filter($appointments ?? [], function($a) {
                        return (\Carbon\Carbon::parse($a['appointment_date'])->isFuture()) && ($a['status'] !== 'cancelled');
                    });
                    $upcoming = array_slice($upcoming, 0, 3);
                @endphp
                
                @forelse($upcoming as $apt)
                <div class="flex items-start space-x-4 p-4 rounded-xl border {{ $apt['status'] == 'approved' ? 'border-emerald-100 bg-emerald-50/30' : 'border-amber-100 bg-amber-50/30' }} hover:shadow-md transition-all group">
                    <div class="flex flex-col items-center justify-center w-14 h-14 bg-white rounded-xl border {{ $apt['status'] == 'approved' ? 'border-emerald-200 text-emerald-600' : 'border-amber-200 text-amber-600' }} shadow-sm flex-shrink-0 group-hover:scale-105 transition-transform">
                        <span class="text-[10px] font-bold uppercase">{{ \Carbon\Carbon::parse($apt['appointment_date'])->format('M') }}</span>
                        <span class="text-xl font-black leading-none mt-0.5">{{ \Carbon\Carbon::parse($apt['appointment_date'])->format('d') }}</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-900 leading-tight">Dr. {{ $apt['doctor_name'] ?? $apt['doctor']['full_name'] ?? 'Doctor' }}</h4>
                        <p class="text-xs text-slate-500 mt-1.5 flex items-center font-medium">
                            <i data-lucide="clock" class="w-3.5 h-3.5 mr-1 text-slate-400"></i>
                            {{ \Carbon\Carbon::parse($apt['appointment_date'])->format('h:i A') }}
                        </p>
                        @if($apt['status'] == 'approved')
                            <span class="inline-block mt-2 px-2 py-0.5 bg-emerald-100 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase tracking-wider rounded-md">Confirmed</span>
                        @else
                            <span class="inline-block mt-2 px-2 py-0.5 bg-amber-100 text-amber-700 border border-amber-200 text-[10px] font-bold uppercase tracking-wider rounded-md">Pending</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-10 flex flex-col items-center justify-center text-slate-400">
                    <i data-lucide="calendar-x-2" class="w-12 h-12 mb-3 opacity-20"></i>
                    <p class="text-sm font-medium">No upcoming appointments.</p>
                    <a href="{{ route('patient.appointments') }}" class="mt-3 text-sm font-bold text-blue-600">Book one now</a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Metrics & Latest Scan -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Vitals Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-blue-200 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Blood Press.</p>
                        <i data-lucide="activity" class="w-4 h-4 text-rose-500 group-hover:animate-pulse"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-800">
                        {{ $latestRecord ? ($latestRecord['systolic'] . '/' . $latestRecord['diastolic']) : '--' }}
                    </p>
                    @if($latestRecord)
                        @if(strtolower($latestRecord['alert_status'] ?? '') == 'critical' || strtolower($latestRecord['alert_status'] ?? '') == 'danger')
                            <span class="text-[11px] text-rose-600 font-bold mt-2 flex items-center bg-rose-50 w-fit px-1.5 py-0.5 rounded"><i data-lucide="alert-triangle" class="w-3 h-3 mr-0.5"></i> Critical</span>
                        @elseif(strtolower($latestRecord['alert_status'] ?? '') == 'warning')
                            <span class="text-[11px] text-amber-600 font-bold mt-2 flex items-center bg-amber-50 w-fit px-1.5 py-0.5 rounded"><i data-lucide="alert-circle" class="w-3 h-3 mr-0.5"></i> Warning</span>
                        @else
                            <span class="text-[11px] text-emerald-600 font-bold mt-2 flex items-center bg-emerald-50 w-fit px-1.5 py-0.5 rounded"><i data-lucide="check" class="w-3 h-3 mr-0.5"></i> Normal</span>
                        @endif
                    @else
                        <span class="text-[11px] text-slate-500 font-bold mt-2 flex items-center bg-slate-100 w-fit px-1.5 py-0.5 rounded"><i data-lucide="minus" class="w-3 h-3 mr-0.5"></i> No Record</span>
                    @endif
                </div>
                
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-blue-200 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Heart Rate</p>
                        <i data-lucide="heart" class="w-4 h-4 text-red-500 group-hover:animate-pulse"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-800">
                        {{ $latestRecord ? $latestRecord['heart_rate'] : '--' }} @if($latestRecord)<span class="text-sm font-bold text-slate-400">bpm</span>@endif
                    </p>
                    @if($latestRecord)
                        @if($latestRecord['heart_rate'] >= 60 && $latestRecord['heart_rate'] <= 100)
                            <span class="text-[11px] text-emerald-600 font-bold mt-2 flex items-center bg-emerald-50 w-fit px-1.5 py-0.5 rounded"><i data-lucide="check" class="w-3 h-3 mr-0.5"></i> Healthy</span>
                        @else
                            <span class="text-[11px] text-amber-600 font-bold mt-2 flex items-center bg-amber-50 w-fit px-1.5 py-0.5 rounded"><i data-lucide="alert-circle" class="w-3 h-3 mr-0.5"></i> Abnormal</span>
                        @endif
                    @else
                        <span class="text-[11px] text-slate-500 font-bold mt-2 flex items-center bg-slate-100 w-fit px-1.5 py-0.5 rounded"><i data-lucide="minus" class="w-3 h-3 mr-0.5"></i> No Record</span>
                    @endif
                </div>
                
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-blue-200 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Weight</p>
                        <i data-lucide="scale" class="w-4 h-4 text-amber-500"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-800">
                        {{ $latestRecord ? $latestRecord['weight'] : '--' }} @if($latestRecord)<span class="text-sm font-bold text-slate-400">kg</span>@endif
                    </p>
                    <span class="text-[11px] text-slate-550 font-medium mt-2 flex items-center">
                        {{ $latestRecord ? 'Recorded' : 'No Record' }}
                    </span>
                </div>
                
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-5 rounded-2xl shadow-lg shadow-slate-900/20 group hover:-translate-y-1 transition-transform relative overflow-hidden">
                    <div class="absolute right-[-10%] bottom-[-10%] opacity-10">
                        <i data-lucide="shield-check" class="w-20 h-20 text-white"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">Health Score</p>
                        </div>
                        <p class="text-3xl font-black text-white">
                            {{ $latestRecord ? $latestRecord['health_score'] : '--' }}@if($latestRecord)<span class="text-sm font-bold text-slate-400">/100</span>@endif
                        </p>
                        @if($latestRecord)
                            @if($latestRecord['health_score'] >= 85)
                                <span class="text-[11px] text-emerald-400 font-bold mt-2 flex items-center"><i data-lucide="trending-up" class="w-3 h-3 mr-1"></i> Excellent</span>
                            @elseif($latestRecord['health_score'] >= 70)
                                <span class="text-[11px] text-blue-400 font-bold mt-2 flex items-center"><i data-lucide="trending-up" class="w-3 h-3 mr-1"></i> Good</span>
                            @else
                                <span class="text-[11px] text-amber-400 font-bold mt-2 flex items-center"><i data-lucide="trending-down" class="w-3 h-3 mr-1"></i> Fair</span>
                            @endif
                        @else
                            <span class="text-[11px] text-slate-400 font-bold mt-2 flex items-center"><i data-lucide="minus" class="w-3 h-3 mr-1"></i> No Record</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Health Trend Chart -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6" x-data="healthChartComponent()">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Recovery Trend</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Track your vitals and recovery diagnostics history</p>
                    </div>
                    @if(count($scoresHistory) > 0)
                    <div class="flex items-center space-x-2">
                        <select x-model="selectedPeriod" @change="updateChart()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg text-slate-650 font-semibold focus:ring-0 px-2.5 py-1.5 outline-none transition-all">
                            <option value="6">Last 6 Records</option>
                            <option value="12">Last 12 Records</option>
                            <option value="all">All Records</option>
                        </select>
                    </div>
                    @endif
                </div>

                @if(count($scoresHistory) > 0)
                    <!-- Metric Tabs -->
                    <div class="flex flex-wrap gap-2 mb-4 border-b border-slate-100 pb-3">
                        <button @click="selectedMetric = 'bp'; updateChart()" :class="selectedMetric === 'bp' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-50 text-slate-650 border-slate-100 hover:bg-slate-100/80'" class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all">
                            <i data-lucide="heart-pulse" class="w-3.5 h-3.5" :class="selectedMetric === 'bp' ? 'text-blue-600' : 'text-slate-405'"></i>
                            <span>Blood Pressure</span>
                        </button>
                        <button @click="selectedMetric = 'hr'; updateChart()" :class="selectedMetric === 'hr' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-50 text-slate-650 border-slate-100 hover:bg-slate-100/80'" class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all">
                            <i data-lucide="heart" class="w-3.5 h-3.5" :class="selectedMetric === 'hr' ? 'text-rose-600' : 'text-slate-405'"></i>
                            <span>Heart Rate</span>
                        </button>
                        <button @click="selectedMetric = 'spo2'; updateChart()" :class="selectedMetric === 'spo2' ? 'bg-cyan-50 text-cyan-700 border-cyan-200' : 'bg-slate-50 text-slate-650 border-slate-100 hover:bg-slate-100/80'" class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all">
                            <i data-lucide="activity" class="w-3.5 h-3.5" :class="selectedMetric === 'spo2' ? 'text-cyan-600' : 'text-slate-405'"></i>
                            <span>Oxygen (SpO2)</span>
                        </button>
                        <button @click="selectedMetric = 'temp'; updateChart()" :class="selectedMetric === 'temp' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-50 text-slate-650 border-slate-100 hover:bg-slate-100/80'" class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all">
                            <i data-lucide="thermometer" class="w-3.5 h-3.5" :class="selectedMetric === 'temp' ? 'text-amber-600' : 'text-slate-405'"></i>
                            <span>Body Temp</span>
                        </button>
                        <button @click="selectedMetric = 'weight'; updateChart()" :class="selectedMetric === 'weight' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-50 text-slate-650 border-slate-100 hover:bg-slate-100/80'" class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all">
                            <i data-lucide="scale" class="w-3.5 h-3.5" :class="selectedMetric === 'weight' ? 'text-indigo-600' : 'text-slate-405'"></i>
                            <span>Weight</span>
                        </button>
                        <button @click="selectedMetric = 'score'; updateChart()" :class="selectedMetric === 'score' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-650 border-slate-100 hover:bg-slate-100/80'" class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all">
                            <i data-lucide="award" class="w-3.5 h-3.5" :class="selectedMetric === 'score' ? 'text-emerald-600' : 'text-slate-405'"></i>
                            <span>Health Score</span>
                        </button>
                    </div>

                    <div id="healthTrendChart" class="w-full min-h-[220px]"></div>
                @else
                    <div class="w-full h-[220px] flex flex-col items-center justify-center text-slate-400 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                        <i data-lucide="line-chart" class="w-8 h-8 mb-2 opacity-30 text-slate-400"></i>
                        <p class="text-xs font-bold text-slate-550">No health tracking history available yet</p>
                        <p class="text-[10px] text-slate-400 mt-1">Metrics recorded during visits will plot your healing journey</p>
                    </div>
                @endif
            </div>

            <!-- Latest Scan Result -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6 hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-32 h-32 bg-blue-50 rounded-bl-full opacity-50 pointer-events-none transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 w-20 h-20 bg-gradient-to-br from-blue-100 to-cyan-100 rounded-2xl flex items-center justify-center flex-shrink-0 text-blue-600 shadow-inner">
                    <i data-lucide="file-check-2" class="w-10 h-10"></i>
                </div>
                <div class="relative z-10 flex-1 text-center sm:text-left">
                    @php
                        $latestScan = $patient['latest_approved_scan'] ?? collect($patient['scans'] ?? [])->where('status', 'approved')->first();
                    @endphp
                    @if($latestScan)
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2">
                            <h4 class="text-lg font-bold text-slate-900">CT Scan Analysis Complete</h4>
                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-md mt-2 sm:mt-0 inline-block w-fit mx-auto sm:mx-0">{{ \Carbon\Carbon::parse($latestScan['created_at'])->format('d M Y') }}</span>
                        </div>
                        <p class="text-sm text-slate-600 leading-relaxed">Your doctor has reviewed your latest scan and updated your clinical record.</p>
                        <a href="{{ route('patient.results') }}" class="inline-flex items-center justify-center mt-4 px-4 py-2 bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white text-sm font-bold rounded-xl transition-colors border border-blue-100 hover:border-blue-600 group-hover:shadow-lg shadow-blue-500/20">
                            View Full Report <i data-lucide="arrow-right" class="w-4 h-4 ml-1.5"></i>
                        </a>
                    @else
                        <h4 class="text-lg font-bold text-slate-900 mb-2">No Recent Scans</h4>
                        <p class="text-sm text-slate-600 leading-relaxed">You don't have any finalized CT scan reports currently. If you've just done a scan, please wait for the doctor's review.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function healthChartComponent() {
    return {
        records: @json($patient['records'] ?? []),
        selectedMetric: 'bp',
        selectedPeriod: '6',
        chart: null,

        init() {
            this.records = [...this.records].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
            this.$nextTick(() => {
                this.renderChart();
            });
        },

        filteredData() {
            let data = [...this.records];
            if (this.selectedPeriod !== 'all') {
                const limit = parseInt(this.selectedPeriod);
                data = data.slice(-limit);
            }
            return data;
        },

        renderChart() {
            const data = this.filteredData();
            if (data.length === 0) return;

            const dates = data.map(r => new Date(r.created_at).toLocaleDateString('en-US', { day: 'numeric', month: 'short' }));
            
            let series = [];
            let colors = ['#3B82F6'];
            let yaxisMin = 0;
            let yaxisMax = 100;
            
            if (this.selectedMetric === 'bp') {
                const systolics = data.map(r => r.systolic ?? 120);
                const diastolics = data.map(r => r.diastolic ?? 80);
                series = [
                    { name: 'Systolic', data: systolics },
                    { name: 'Diastolic', data: diastolics }
                ];
                colors = ['#3B82F6', '#06B6D4'];
                const allBP = [...systolics, ...diastolics];
                yaxisMin = Math.max(0, Math.min(...allBP) - 15);
                yaxisMax = Math.max(...allBP) + 15;
            } else {
                let yData = [];
                let name = '';
                if (this.selectedMetric === 'hr') {
                    yData = data.map(r => r.heart_rate ?? 70);
                    name = 'Heart Rate (bpm)';
                    colors = ['#EF4444'];
                    yaxisMin = Math.max(0, Math.min(...yData) - 10);
                    yaxisMax = Math.max(...yData) + 10;
                } else if (this.selectedMetric === 'spo2') {
                    yData = data.map(r => r.oxygen_level ?? 98);
                    name = 'Oxygen Saturation (%)';
                    colors = ['#06B6D4'];
                    yaxisMin = Math.max(0, Math.min(...yData) - 3);
                    yaxisMax = 100;
                } else if (this.selectedMetric === 'temp') {
                    yData = data.map(r => r.temperature ?? 36.5);
                    name = 'Body Temp (°C)';
                    colors = ['#F59E0B'];
                    yaxisMin = Math.max(0, Math.min(...yData) - 1);
                    yaxisMax = Math.max(...yData) + 1;
                } else if (this.selectedMetric === 'weight') {
                    yData = data.map(r => r.weight ?? 70);
                    name = 'Weight (kg)';
                    colors = ['#6366F1'];
                    yaxisMin = Math.max(0, Math.min(...yData) - 10);
                    yaxisMax = Math.max(...yData) + 10;
                } else if (this.selectedMetric === 'score') {
                    yData = data.map(r => r.health_score ?? 90);
                    name = 'Health Score';
                    colors = ['#10B981'];
                    yaxisMin = 0;
                    yaxisMax = 100;
                }
                series = [{ name: name, data: yData }];
            }

            const options = {
                series: series,
                chart: {
                    type: 'area',
                    height: 220,
                    fontFamily: 'Inter, sans-serif',
                    toolbar: { show: false },
                    animations: { enabled: true, easing: 'easeinout', speed: 400 }
                },
                colors: colors,
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.3,
                        opacityTo: 0.01,
                        stops: [0, 90, 100]
                    }
                },
                dataLabels: { enabled: true, style: { fontSize: '9px', fontWeight: 'bold', colors: ['#475569'] } },
                stroke: { curve: 'smooth', width: 3 },
                xaxis: {
                    categories: dates,
                    axisBorder: { show: true, color: '#cbd5e1' },
                    axisTicks: { show: true, color: '#cbd5e1' },
                    labels: { style: { colors: '#64748B', fontSize: '10px', fontWeight: 500 } }
                },
                yaxis: {
                    show: true,
                    axisBorder: { show: true, color: '#cbd5e1' },
                    axisTicks: { show: true, color: '#cbd5e1' },
                    min: Math.round(yaxisMin),
                    max: Math.round(yaxisMax),
                    labels: { style: { colors: '#64748B', fontSize: '9px' } }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 4,
                    xaxis: { lines: { show: false } },
                    yaxis: { lines: { show: true } }
                },
                tooltip: { shared: true, intersect: false }
            };

            if (this.chart) {
                this.chart.destroy();
            }
            this.chart = new ApexCharts(document.querySelector("#healthTrendChart"), options);
            this.chart.render();
            
            // Re-trigger Lucide icons to ensure icons render properly on update
            setTimeout(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); }, 50);
        },

        updateChart() {
            this.renderChart();
        }
    }
}
</script>
@endsection
