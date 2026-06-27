@extends('layouts.dashboard')

@section('title', 'Patient Diagnostic Portfolio')

@section('dashboard_content')
<div class="max-w-7xl mx-auto space-y-8" x-data="patientPortfolio()">
    
    <!-- Top Patient Info Glass Banner (Dark Glassmorphism) -->
    <div class="relative overflow-hidden rounded-[2.5rem] bg-slate-900 border border-slate-800 shadow-2xl p-8 lg:p-10 text-white">
        <!-- Abstract Background Orbs -->
        <div class="absolute -top-32 -right-32 w-96 h-96 bg-blue-600/30 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-indigo-600/30 rounded-full blur-[100px] pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
            <div class="flex items-center space-x-6">
                <!-- Avatar -->
                <div class="relative">
                    <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-4xl font-black shadow-lg shadow-blue-500/30 border border-white/10 ring-4 ring-slate-800/50">
                        {{ substr($patient['full_name'] ?? 'P', 0, 1) }}
                    </div>
                    <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-emerald-500 rounded-xl border-[3px] border-slate-900 flex items-center justify-center shadow-lg">
                        <i data-lucide="check" class="w-4 h-4 text-white"></i>
                    </div>
                </div>
                
                <!-- Info -->
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <h2 class="text-3xl font-black tracking-tight text-white">{{ $patient['full_name'] ?? 'Unknown Patient' }}</h2>
                        <span class="px-3 py-1 bg-white/10 border border-white/20 text-blue-300 rounded-xl text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                            ID: #{{ str_pad($patient['id'] ?? 0, 5, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center text-sm text-slate-300 gap-x-6 gap-y-3 font-medium">
                        <span class="flex items-center bg-slate-800/50 px-3 py-1.5 rounded-lg border border-slate-700/50 backdrop-blur-sm"><i data-lucide="calendar" class="w-4 h-4 mr-2 text-blue-400"></i> DOB: {{ $patient['dob'] ?? 'N/A' }}</span>
                        <span class="flex items-center bg-slate-800/50 px-3 py-1.5 rounded-lg border border-slate-700/50 backdrop-blur-sm"><i data-lucide="user" class="w-4 h-4 mr-2 text-indigo-400"></i> Gender: {{ $patient['gender'] ?? 'Not Specified' }}</span>
                        <span class="flex items-center bg-slate-800/50 px-3 py-1.5 rounded-lg border border-slate-700/50 backdrop-blur-sm"><i data-lucide="phone" class="w-4 h-4 mr-2 text-emerald-400"></i> {{ $patient['phone'] ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-3">
                <a href="{{ route('doctor.dashboard') }}" class="w-full sm:w-auto px-6 py-3 bg-slate-800/50 hover:bg-slate-700/50 border border-slate-700 text-white text-sm font-bold rounded-2xl transition-all shadow-sm backdrop-blur-md text-center">
                    Dashboard
                </a>
                <a href="{{ route('doctor.scans.queue') }}" class="w-full sm:w-auto px-6 py-3 bg-blue-600 hover:bg-blue-500 border border-blue-500 text-white text-sm font-bold rounded-2xl transition-all shadow-[0_0_20px_rgba(37,99,235,0.3)] hover:shadow-[0_0_30px_rgba(37,99,235,0.5)] flex items-center justify-center space-x-2">
                    <i data-lucide="stethoscope" class="w-4 h-4"></i>
                    <span>Triage Scans</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Health Analytics & History (8 columns) -->
        <div class="lg:col-span-8 space-y-8">
            
            <!-- Health Analytics Chart Panel -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                    <div>
                        <h3 class="font-black text-slate-900 text-xl tracking-tight">Clinical Vitals Analytics</h3>
                        <p class="text-sm text-slate-500 mt-1 font-medium">Physiological tracking and trend analysis.</p>
                    </div>
                    
                    <div class="flex items-center p-1 bg-slate-100/80 backdrop-blur-sm rounded-2xl border border-slate-200/50">
                        <button @click="setFilter('weekly')" :class="filter === 'weekly' ? 'bg-white text-blue-600 shadow-sm border-slate-200' : 'text-slate-500 hover:text-slate-700 border-transparent'" class="px-5 py-2 rounded-xl text-xs font-bold transition-all border">1W</button>
                        <button @click="setFilter('monthly')" :class="filter === 'monthly' ? 'bg-white text-blue-600 shadow-sm border-slate-200' : 'text-slate-500 hover:text-slate-700 border-transparent'" class="px-5 py-2 rounded-xl text-xs font-bold transition-all border">1M</button>
                        <button @click="setFilter('yearly')" :class="filter === 'yearly' ? 'bg-white text-blue-600 shadow-sm border-slate-200' : 'text-slate-500 hover:text-slate-700 border-transparent'" class="px-5 py-2 rounded-xl text-xs font-bold transition-all border">1Y</button>
                    </div>
                </div>

                <div class="relative h-[360px] w-full rounded-2xl overflow-hidden bg-slate-50/50 border border-slate-100 p-4">
                    <div x-show="loading" x-transition.opacity class="absolute inset-0 bg-white/60 backdrop-blur-md z-10 flex items-center justify-center">
                        <div class="flex flex-col items-center">
                            <i data-lucide="loader-2" class="w-8 h-8 text-blue-600 animate-spin mb-3"></i>
                            <span class="text-xs font-bold text-slate-600 uppercase tracking-widest">Syncing Data...</span>
                        </div>
                    </div>
                    <div class="w-full h-full transition-all duration-500" :class="loading ? 'scale-[0.98] blur-sm opacity-50' : 'scale-100 opacity-100'">
                        <canvas id="patientClinicalChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- CT Scan Imaging History -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">
                <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center">
                            <i data-lucide="scan" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h4 class="font-black text-slate-900 text-lg tracking-tight">Diagnostic Imaging</h4>
                            <p class="text-sm text-slate-500 font-medium">Neural network assessments</p>
                        </div>
                    </div>
                    <span class="px-4 py-1.5 bg-slate-900 text-white text-xs font-bold rounded-xl shadow-lg">
                        {{ count($patient['scans'] ?? []) }} Scans
                    </span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase tracking-widest font-bold">
                            <tr>
                                <th class="px-8 py-5">Date</th>
                                <th class="px-8 py-5">AI Result</th>
                                <th class="px-8 py-5">Risk</th>
                                <th class="px-8 py-5">Status</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-700">
                            @forelse($patient['scans'] ?? [] as $scan)
                                <tr class="hover:bg-blue-50/50 transition-colors group">
                                    <td class="px-8 py-5 text-slate-900 font-bold">
                                        {{ \Carbon\Carbon::parse($scan['created_at'] ?? 'now')->format('d M Y') }}
                                    </td>
                                    <td class="px-8 py-5 text-indigo-600 font-bold">
                                        {{ $scan['ai_result']['prediction_label'] ?? 'Clear Scan' }}
                                    </td>
                                    <td class="px-8 py-5">
                                        @php
                                            $rLevel = strtolower($scan['ai_result']['risk_level'] ?? '');
                                            $sevClass = 'bg-emerald-100 text-emerald-700';
                                            if (str_contains($rLevel, 'high') || str_contains($rLevel, 'critical')) {
                                                $sevClass = 'bg-rose-100 text-rose-700';
                                            } elseif (str_contains($rLevel, 'medium') || str_contains($rLevel, 'warning')) {
                                                $sevClass = 'bg-amber-100 text-amber-700';
                                            }
                                        @endphp
                                        <span class="px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider {{ $sevClass }}">
                                            {{ $scan['ai_result']['risk_level'] ?? 'Low Risk' }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5">
                                        @php
                                            $stat = strtolower($scan['status'] ?? 'pending');
                                        @endphp
                                        @if($stat == 'uploaded')
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200">
                                                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-2"></span> Uploaded
                                            </span>
                                        @elseif($stat == 'ai_processing')
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                                <i data-lucide="loader-2" class="w-3.5 h-3.5 mr-1.5 animate-spin"></i> Processing
                                            </span>
                                        @elseif($stat == 'pending_review' || $stat == 'pending')
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full mr-2 shadow-[0_0_8px_rgba(99,102,241,0.6)]"></span> Review Queue
                                            </span>
                                        @elseif($stat == 'approved')
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span> Approved
                                            </span>
                                        @elseif($stat == 'rejected')
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-100">
                                                <span class="w-1.5 h-1.5 bg-rose-500 rounded-full mr-2 shadow-[0_0_8px_rgba(244,63,94,0.8)]"></span> Rejected
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 border border-slate-200">
                                                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-2"></span> {{ $stat }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <a href="{{ route('doctor.scans.review', $scan['id'] ?? 0) }}" class="inline-flex items-center justify-center w-10 h-10 bg-slate-100 hover:bg-blue-600 text-slate-600 hover:text-white rounded-xl transition-all shadow-sm">
                                            <i data-lucide="arrow-right" class="w-5 h-5"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-8 py-16 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                                <i data-lucide="image-off" class="w-8 h-8 text-slate-300"></i>
                                            </div>
                                            <p class="font-bold text-slate-600">No Scans Found</p>
                                            <p class="text-sm mt-1">This patient does not have any diagnostic history.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Vitals Dashboard & Clinical Log (4 columns) -->
        <div class="lg:col-span-4 space-y-8">
            
            <!-- Latest Vitals Grid -->
            @php
                $latest = !empty($patient['records']) ? end($patient['records']) : null;
                $hScore = $latest['health_score'] ?? 85;
                $alertStat = strtolower($latest['alert_status'] ?? 'normal');
                
                $scoreColor = 'from-emerald-400 to-emerald-600 shadow-emerald-500/30';
                $alertMsg = 'Patient physiological signals are completely stable.';
                if ($alertStat == 'critical' || $hScore < 50) {
                    $scoreColor = 'from-rose-400 to-rose-600 shadow-rose-500/30 animate-pulse';
                    $alertMsg = 'CRITICAL: Immediate attention required.';
                } elseif ($alertStat == 'warning' || $hScore <= 75) {
                    $scoreColor = 'from-amber-400 to-amber-600 shadow-amber-500/30';
                    $alertMsg = 'Warning: Suboptimal vitals detected.';
                }
            @endphp
            
            <!-- Vitals Card (Premium UI) -->
            <div class="bg-slate-900 rounded-[2rem] border border-slate-800 shadow-2xl p-8 text-white relative overflow-hidden">
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5 mix-blend-overlay"></div>
                <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex items-center justify-between mb-8">
                    <div>
                        <h4 class="font-black text-white text-lg">Current Vitals</h4>
                        <p class="text-slate-400 text-xs mt-1">Last updated: {{ $latest ? \Carbon\Carbon::parse($latest['created_at'])->diffForHumans() : 'N/A' }}</p>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br {{ $scoreColor }} flex items-center justify-center font-black text-xl shadow-lg border-2 border-slate-900">
                            {{ $hScore }}
                        </div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-2">Score</span>
                    </div>
                </div>

                <div class="relative z-10 grid grid-cols-2 gap-4 mb-6">
                    <!-- BP -->
                    <div class="bg-slate-800/50 backdrop-blur-md p-5 rounded-2xl border border-slate-700/50 hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center"><i data-lucide="activity" class="w-4 h-4"></i></div>
                            <span class="text-xs font-bold text-slate-400">BP</span>
                        </div>
                        <p class="text-xl font-black text-white">{{ $latest['systolic'] ?? 120 }}/<span class="text-slate-400">{{ $latest['diastolic'] ?? 80 }}</span></p>
                    </div>
                    <!-- HR -->
                    <div class="bg-slate-800/50 backdrop-blur-md p-5 rounded-2xl border border-slate-700/50 hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center"><i data-lucide="heart" class="w-4 h-4"></i></div>
                            <span class="text-xs font-bold text-slate-400">BPM</span>
                        </div>
                        <p class="text-xl font-black text-white">{{ $latest['heart_rate'] ?? 72 }}</p>
                    </div>
                    <!-- Sugar -->
                    <div class="bg-slate-800/50 backdrop-blur-md p-5 rounded-2xl border border-slate-700/50 hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center"><i data-lucide="droplet" class="w-4 h-4"></i></div>
                            <span class="text-xs font-bold text-slate-400">Sugar</span>
                        </div>
                        <p class="text-xl font-black text-white">{{ $latest['blood_sugar'] ?? 95 }}</p>
                    </div>
                    <!-- SpO2 -->
                    <div class="bg-slate-800/50 backdrop-blur-md p-5 rounded-2xl border border-slate-700/50 hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center space-x-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center"><i data-lucide="wind" class="w-4 h-4"></i></div>
                            <span class="text-xs font-bold text-slate-400">SpO2</span>
                        </div>
                        <p class="text-xl font-black text-white">{{ $latest['oxygen_level'] ?? 98 }}%</p>
                    </div>
                </div>

                <div class="relative z-10 bg-slate-950/50 p-4 rounded-xl border border-slate-800 flex items-start space-x-3">
                    <i data-lucide="info" class="w-5 h-5 text-slate-400 shrink-0 mt-0.5"></i>
                    <p class="text-sm font-medium text-slate-300 leading-snug">{{ $alertMsg }}</p>
                </div>
            </div>

            <!-- Health record timeline history log -->
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-8">
                <div class="flex items-center space-x-3 mb-8">
                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="history" class="w-5 h-5"></i>
                    </div>
                    <h4 class="font-black text-slate-900 text-lg tracking-tight">Timeline Log</h4>
                </div>
                
                <div class="space-y-6 relative before:absolute before:inset-0 before:ml-[11px] before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-blue-200 before:to-transparent pl-8 border-none">
                    @forelse($patient['records'] as $rec)
                        <div class="relative mb-6 last:mb-0 group">
                            <!-- Dot -->
                            <div class="absolute -left-10 top-1 w-6 h-6 bg-white border-4 border-blue-100 rounded-full flex items-center justify-center shadow-sm group-hover:border-blue-500 transition-colors">
                                <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                            </div>
                            
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 group-hover:shadow-md transition-shadow group-hover:border-blue-100">
                                <div class="flex justify-between items-start mb-2">
                                    <h5 class="text-sm font-bold text-slate-800">Checkup Log</h5>
                                    <span class="text-[10px] font-black text-blue-600 bg-blue-50 px-2 py-1 rounded-md">{{ \Carbon\Carbon::parse($rec['created_at'] ?? 'now')->format('d M y') }}</span>
                                </div>
                                <p class="text-xs font-semibold text-slate-500 mb-2">Score: <span class="text-slate-700 font-black">{{ $rec['health_score'] ?? 80 }}</span></p>
                                @if(!empty($rec['notes']))
                                    <div class="bg-white p-3 rounded-xl border border-slate-100 text-xs text-slate-600 italic">
                                        {{ $rec['notes'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-slate-400 font-bold text-sm bg-slate-50 p-6 rounded-2xl border border-slate-100 border-dashed">
                            No logs recorded.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function patientPortfolio() {
    return {
        filter: 'monthly',
        loading: false,
        chartInstance: null,
        rawRecords: @json($patient['records'] ?? []),

        init() {
            this.$nextTick(() => {
                this.renderChart();
            });
        },

        setFilter(val) {
            this.loading = true;
            this.filter = val;
            setTimeout(() => {
                this.updateChartData();
                this.loading = false;
            }, 600);
        },

        renderChart() {
            const ctx = document.getElementById('patientClinicalChart').getContext('2d');
            const chartData = this.getFilteredDataset();
            
            // Premium gradients for chart
            const gradientBlue = ctx.createLinearGradient(0, 0, 0, 300);
            gradientBlue.addColorStop(0, 'rgba(37, 99, 235, 0.2)');
            gradientBlue.addColorStop(1, 'rgba(37, 99, 235, 0)');

            const gradientRose = ctx.createLinearGradient(0, 0, 0, 300);
            gradientRose.addColorStop(0, 'rgba(244, 63, 94, 0.2)');
            gradientRose.addColorStop(1, 'rgba(244, 63, 94, 0)');

            this.chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'BP Systolic',
                            data: chartData.systolic,
                            borderColor: '#2563EB',
                            backgroundColor: gradientBlue,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#2563EB',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Heart Rate',
                            data: chartData.heartRate,
                            borderColor: '#F43F5E',
                            backgroundColor: gradientRose,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#F43F5E',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleFont: { size: 13, family: 'Inter' },
                            bodyFont: { size: 12, family: 'Inter', weight: 'bold' },
                            padding: 12,
                            cornerRadius: 12,
                            displayColors: true,
                        }
                    },
                    scales: {
                        y: {
                            grid: { color: '#f1f5f9', drawBorder: true },
                            border: { display: true, color: '#cbd5e1' },
                            ticks: { font: { size: 11, family: 'Inter', weight: 'bold' }, color: '#94a3b8', padding: 10 }
                        },
                        x: {
                            grid: { display: true, color: '#f1f5f9', drawBorder: true },
                            border: { display: true, color: '#cbd5e1' },
                            ticks: { font: { size: 11, family: 'Inter', weight: 'bold' }, color: '#94a3b8', padding: 10 }
                        }
                    }
                }
            });
        },

        updateChartData() {
            if (!this.chartInstance) return;
            const updated = this.getFilteredDataset();
            
            this.chartInstance.data.labels = updated.labels;
            this.chartInstance.data.datasets[0].data = updated.systolic;
            this.chartInstance.data.datasets[1].data = updated.heartRate;
            this.chartInstance.update();
        },

        getFilteredDataset() {
            let recordsToUse = [...this.rawRecords];
            
            if (this.filter === 'weekly') recordsToUse = recordsToUse.slice(-7);
            else if (this.filter === 'monthly') recordsToUse = recordsToUse.slice(-30);
            else recordsToUse = recordsToUse.slice(-12);

            const labels = [];
            const systolic = [];
            const heartRate = [];

            recordsToUse.forEach(r => {
                const date = new Date(r.created_at || new Date());
                labels.push(date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }));
                systolic.push(r.systolic || 120);
                heartRate.push(r.heart_rate || 72);
            });

            if (labels.length === 0) {
                return {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    systolic: [120, 118, 125, 122, 120],
                    heartRate: [72, 75, 70, 74, 72]
                };
            }
            return { labels, systolic, heartRate };
        }
    }
}
</script>
@endsection
