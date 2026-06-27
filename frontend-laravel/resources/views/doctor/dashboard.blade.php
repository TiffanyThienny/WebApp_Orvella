@extends('layouts.dashboard')

@section('title', 'Clinical Command Center')

@section('dashboard_content')
<div class="space-y-8 max-w-7xl mx-auto">
    <!-- Welcome Header Banner -->
    <div class="bg-white rounded-3xl p-6 lg:p-8 border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-96 h-96 bg-gradient-to-bl from-blue-50/70 via-transparent to-transparent rounded-bl-full pointer-events-none opacity-60"></div>
        
        <div class="relative z-10 flex items-center space-x-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white flex items-center justify-center shadow-lg shadow-blue-500/30">
                <i data-lucide="stethoscope" class="w-8 h-8"></i>
            </div>
            <div>
                <h2 class="text-3xl font-black text-slate-800 tracking-tight">Hello, Dr. {{ session('user')['full_name'] ?? 'Doctor' }}</h2>
                <p class="text-xs text-slate-500 mt-1">Here is your high-priority patient triage queue and clinical validation worklist.</p>
            </div>
        </div>

        <div class="flex space-x-3 relative z-10">
            <div class="px-4 py-2.5 bg-rose-50 text-rose-700 rounded-2xl border border-rose-100 flex items-center font-bold text-xs">
                <div class="w-2.5 h-2.5 rounded-full bg-rose-600 animate-pulse mr-2 shadow-sm shadow-rose-500/30"></div>
                {{ count($urgentApprovals) }} Priority Action
            </div>
            <div class="px-4 py-2.5 bg-blue-50 text-blue-700 rounded-2xl border border-blue-100 flex items-center font-bold text-xs">
                <i data-lucide="inbox" class="w-4 h-4 mr-2"></i>
                {{ count($pendingReviews) }} In Queue
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Left Column: Priority Clinical Reviews (oldest pending first) -->
        <div class="xl:col-span-2 space-y-8">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm flex flex-col">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-rose-50 text-rose-600 rounded-2xl"><i data-lucide="activity" class="w-5 h-5"></i></div>
                        <div>
                            <h3 class="font-extrabold text-slate-800 text-base">Priority Clinical Reviews</h3>
                            <p class="text-xs text-slate-550">Sorted oldest pending first (FIFO). You have {{ count($pendingReviews) }} scans to approve today.</p>
                        </div>
                    </div>
                    <a href="{{ route('doctor.scans.queue') }}" class="px-4 py-2 bg-slate-50 hover:bg-blue-50 text-blue-650 hover:text-blue-750 text-xs font-bold rounded-xl border border-slate-200 hover:border-blue-200 transition-colors">Open Full Queue</a>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($urgentApprovals as $index => $scan)
                        @php
                            // Calculate simple urgency score based on risk level or prediction cytology class
                            $risk = strtolower($scan['ai_result']['risk_level'] ?? $scan['ai_result']['prediction_label'] ?? '');
                            $urgencyScore = 25;
                            $urgencyColor = 'bg-slate-100 text-slate-700 border-slate-200';
                            
                            if (str_contains($risk, 'tumor') || str_contains($risk, 'critical') || str_contains($risk, 'high') || str_contains($risk, 'dyskeratotic')) {
                                $urgencyScore = 94;
                                $urgencyColor = 'bg-rose-50 text-rose-700 border-rose-200';
                            } elseif (str_contains($risk, 'medium') || str_contains($risk, 'abnormal') || str_contains($risk, 'koilocytotic')) {
                                $urgencyScore = 68;
                                $urgencyColor = 'bg-amber-50 text-amber-700 border-amber-200';
                            } elseif (str_contains($risk, 'metaplastic') || str_contains($risk, 'parabasal')) {
                                $urgencyScore = 45;
                                $urgencyColor = 'bg-blue-50 text-blue-700 border-blue-100';
                            } else {
                                $urgencyScore = 20; // Superficial-Intermediate (Normal)
                                $urgencyColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            }
                        @endphp
                        <div class="bg-slate-50 rounded-3xl p-5 border border-slate-200 hover:border-blue-300 hover:shadow-md transition-all group relative overflow-hidden flex flex-col justify-between h-[280px]">
                            <div class="absolute top-0 right-0 w-28 h-28 bg-gradient-to-bl from-blue-200 to-transparent opacity-20 rounded-bl-full pointer-events-none"></div>
                            
                            <!-- Queue position absolute badge -->
                            <div class="absolute right-4 top-4 px-2.5 py-1 bg-slate-800 text-white rounded-lg text-[9px] font-black uppercase tracking-wider">
                                Pos #{{ $index + 1 }}
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border tracking-wider inline-block {{ $urgencyColor }}">
                                            Urgency: {{ $urgencyScore }}%
                                        </span>
                                        <span class="px-2.5 py-1 text-[9px] font-black text-rose-600 bg-rose-50 border border-rose-200 rounded-lg flex items-center uppercase tracking-wider" title="Duration scan has remained unreviewed">
                                            <i data-lucide="clock" class="w-3.5 h-3.5 mr-1 text-rose-500 animate-pulse"></i>
                                            {{ \Carbon\Carbon::parse($scan['created_at'])->diffForHumans(null, true) }}
                                        </span>
                                    </div>
                                    <h4 class="text-base font-black text-slate-800 leading-tight mt-2 truncate">{{ $scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'Patient' }}</h4>
                                </div>
                                
                                <div class="bg-white p-3 rounded-2xl border border-slate-100 shadow-inner">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-[10px] text-slate-450 font-bold uppercase tracking-wider">AI Confidence</span>
                                        <span class="text-[10px] font-extrabold text-blue-600">{{ number_format(($scan['ai_result']['confidence'] ?? 0) * 100, 1) }}%</span>
                                    </div>
                                    <p class="text-xs font-black text-slate-800 uppercase tracking-wide truncate flex items-center">
                                        <i data-lucide="cpu" class="w-3.5 h-3.5 mr-1.5 text-blue-500 animate-pulse"></i>
                                        {{ $scan['ai_result']['prediction_label'] ?? 'Awaiting' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('doctor.scans.review', $scan['id']) }}" class="w-full flex items-center justify-center py-3 bg-white hover:bg-blue-600 text-blue-600 hover:text-white border border-blue-200 hover:border-blue-600 text-xs font-bold rounded-2xl transition-all shadow-sm hover:shadow-lg hover:shadow-blue-500/20">
                                    <span>Initiate Clinical Review</span>
                                    <i data-lucide="arrow-right" class="w-4 h-4 ml-1.5 group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 p-16 flex flex-col items-center justify-center text-slate-400 bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
                            <i data-lucide="smile" class="w-12 h-12 mb-3 text-emerald-500 animate-bounce"></i>
                            <h5 class="text-sm font-bold text-slate-600">All Scans Approved</h5>
                            <p class="text-xs text-slate-450 mt-1">No urgent clinical scan validations pending today.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Dashboard Analytics Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center"><i data-lucide="bar-chart-3" class="w-4 h-4 mr-1.5 text-blue-500"></i> Review Activity</h3>
                        <span class="text-[10px] text-slate-400 font-bold uppercase">This Week</span>
                    </div>
                    <div id="reviewActivityChart" class="w-full h-[220px]"></div>
                </div>
                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center"><i data-lucide="pie-chart" class="w-4 h-4 mr-1.5 text-indigo-500"></i> Triage Breakdown</h3>
                        <span class="text-[10px] text-slate-400 font-bold uppercase">Realtime</span>
                    </div>
                    <div id="diagnosisChart" class="w-full h-[220px]"></div>
                </div>
            </div>
        </div>

        <!-- Right Column: Queue list register -->
        <div class="space-y-8">
            <!-- Stats overview cards -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-3xl p-5 text-white shadow-xl relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-15 group-hover:scale-115 transition-transform duration-300">
                        <i data-lucide="file-check" class="w-24 h-24"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Reviewed Today</p>
                        <h4 class="text-3xl font-black">{{ $reviewedToday }}</h4>
                        <p class="text-[9px] text-blue-200 mt-2 font-medium flex items-center"><i data-lucide="trending-up" class="w-3 h-3 mr-1"></i> +14% from avg</p>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-3xl p-5 text-white shadow-xl relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 opacity-15 group-hover:scale-115 transition-transform duration-300">
                        <i data-lucide="clock" class="w-24 h-24"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-bold text-indigo-100 uppercase tracking-widest mb-1">Avg Triaged</p>
                        <h4 class="text-3xl font-black">{{ $avgReviewTime }}</h4>
                        <p class="text-[9px] text-indigo-200 mt-2 font-medium flex items-center"><i data-lucide="trending-down" class="w-3 h-3 mr-1"></i> -8% faster</p>
                    </div>
                </div>
            </div>

            <!-- Active Schedule Widget -->
            <a href="{{ route('doctor.appointments') }}" class="block bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden group border border-slate-700 hover:border-slate-500 hover:shadow-xl transition-all cursor-pointer">
                <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500">
                    <i data-lucide="calendar" class="w-32 h-32"></i>
                </div>
                <div class="relative z-10 space-y-4">
                    <div>
                        <h3 class="text-sm font-black text-white tracking-wide uppercase">{{ $scheduleTitle }}</h3>
                        <p class="text-[10px] text-slate-400 font-bold mt-0.5">{{ $scheduleDate }}</p>
                    </div>

                    @if($activeSchedule)
                        <div class="bg-slate-800/80 rounded-2xl p-4 border border-slate-700 backdrop-blur-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Working Hours</span>
                                    <div class="flex items-center text-sm font-black text-emerald-400">
                                        <i data-lucide="clock" class="w-4 h-4 mr-1.5"></i>
                                        {{ \Carbon\Carbon::parse($activeSchedule['start_time'])->format('H:i') }} - {{ \Carbon\Carbon::parse($activeSchedule['end_time'])->format('H:i') }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Max Quota</span>
                                    <div class="text-sm font-black text-white flex items-center justify-end">
                                        <i data-lucide="users" class="w-4 h-4 mr-1.5 text-blue-400"></i>
                                        {{ $activeSchedule['max_patients'] }} Patients
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-slate-800/50 rounded-2xl p-5 border border-slate-700 border-dashed text-center">
                            <i data-lucide="calendar-off" class="w-6 h-6 mx-auto mb-2 text-slate-500"></i>
                            <p class="text-xs font-bold text-slate-400">No active assignment</p>
                        </div>
                    @endif
                </div>
            </a>

            <!-- Waitlist queue list -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col h-[400px]">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="text-sm font-extrabold text-slate-800">Diagnostic Review Queue</h3>
                    <span class="px-2.5 py-1 bg-slate-200/60 text-slate-700 text-[10px] font-black rounded-lg">{{ max(0, count($pendingReviews) - 4) }} awaiting</span>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar p-3 space-y-2">
                    @foreach(array_slice($pendingReviews, 4) as $scan)
                        <div class="p-4 bg-white rounded-2xl border border-slate-100 hover:border-blue-200 hover:shadow-sm transition-all group flex items-center justify-between">
                            <div class="flex items-center space-x-3 overflow-hidden">
                                <div class="w-8 h-8 rounded-xl bg-slate-50 border border-slate-200 text-slate-500 flex items-center justify-center text-xs font-black flex-shrink-0">
                                    {{ substr($scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'U', 0, 1) }}
                                </div>
                                <div class="overflow-hidden">
                                    <h5 class="text-xs font-bold text-slate-800 truncate mb-0.5 group-hover:text-blue-600 transition-colors">{{ $scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'Patient' }}</h5>
                                    <p class="text-[9px] text-slate-450 font-bold uppercase truncate flex items-center"><i data-lucide="cpu" class="w-2.5 h-2.5 inline mr-1 text-slate-400"></i> {{ $scan['ai_result']['prediction_label'] ?? 'Awaiting' }}</p>
                                </div>
                            </div>
                            <a href="{{ route('doctor.scans.review', $scan['id']) }}" class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white flex items-center justify-center transition-all flex-shrink-0">
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    @endforeach
                    
                    @if(count($pendingReviews) <= 4)
                        <div class="p-8 text-center text-slate-450 flex flex-col items-center justify-center h-full my-auto">
                            <i data-lucide="check-circle" class="w-8 h-8 mb-2 text-emerald-500 animate-pulse"></i>
                            <p class="text-[11px] font-extrabold text-slate-700">No additional items</p>
                            <p class="text-[9px] text-slate-400 mt-1 leading-relaxed max-w-[180px]">All pending scans are already shown in the Priority Cards on the left.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Review Activity Chart
    var optionsActivity = {
        series: [{ name: 'Reviews Completed', data: @json($activityData) }],
        chart: { type: 'area', height: 220, fontFamily: 'Inter, sans-serif', toolbar: { show: false } },
        colors: ['#2563EB'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.02, stops: [0, 90, 100] } },
        stroke: { curve: 'smooth', width: 3 },
        xaxis: {
            categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            axisBorder: { show: true, color: '#cbd5e1' },
            axisTicks: { show: true, color: '#cbd5e1' },
            labels: { style: { colors: '#64748B', fontSize: '10px' } }
        },
        yaxis: {
            show: true,
            axisBorder: { show: true, color: '#cbd5e1' },
            axisTicks: { show: true, color: '#cbd5e1' },
            labels: { style: { colors: '#64748B', fontSize: '10px' } }
        },
        grid: {
            borderColor: '#f1f5f9',
            strokeDashArray: 4,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } }
        },
        tooltip: { shared: true, intersect: false }
    };
    new ApexCharts(document.querySelector("#reviewActivityChart"), optionsActivity).render();

    // Triage breakdown donut chart
    var optionsDiag = {
        series: [@json($clearCount), @json($minorCount), @json($criticalCount)],
        labels: ['Clear Scans', 'Minor Anomalies', 'Critical Care'],
        chart: { type: 'donut', height: 220, fontFamily: 'Inter, sans-serif' },
        colors: ['#10B981', '#F59E0B', '#EF4444'],
        plotOptions: { pie: { donut: { size: '65%' } } },
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontSize: '11px', fontWeight: 'bold' },
        stroke: { show: false }
    };
    new ApexCharts(document.querySelector("#diagnosisChart"), optionsDiag).render();
});
</script>
@endsection
