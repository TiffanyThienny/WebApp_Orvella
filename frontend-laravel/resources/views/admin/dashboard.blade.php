@extends('layouts.dashboard')

@section('title', 'System Administration')

@section('dashboard_content')
@php
    $systemStats = $analytics['system_stats'] ?? ['total_users' => 0, 'total_scans' => 0, 'total_records' => 0];
    $roleDistribution = $analytics['role_distribution'] ?? [];
    $patientGrowth = $analytics['patient_growth'] ?? [];
@endphp

<div class="space-y-6 max-w-7xl mx-auto">
    
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-900 to-cyan-800 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
        <div class="absolute right-0 top-0 opacity-10 pointer-events-none">
            <i data-lucide="activity" class="w-64 h-64 -mt-10 -mr-10"></i>
        </div>
        <div class="relative z-10">
            <h2 class="text-3xl font-bold mb-2">Welcome Back, {{ session('user')['full_name'] ?? 'Admin' }}! 👋</h2>
            <p class="text-blue-100 max-w-xl">Here is what's happening with your Orvella platform today. System AI models are running smoothly and the database is fully synced.</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Stat Card 1 -->
        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow group relative overflow-hidden">
            <div class="absolute right-[-10px] top-[-10px] bg-blue-50 w-24 h-24 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm font-semibold text-slate-500 mb-1">Total Users</p>
                    <h3 class="text-3xl font-bold text-slate-800">{{ $systemStats['total_users'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-emerald-500 flex items-center font-medium"><i data-lucide="trending-up" class="w-4 h-4 mr-1"></i> +12%</span>
                <span class="text-slate-400 ml-2">from last month</span>
            </div>
        </div>

        <!-- Stat Card 2 -->
        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow group relative overflow-hidden">
            <div class="absolute right-[-10px] top-[-10px] bg-cyan-50 w-24 h-24 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm font-semibold text-slate-500 mb-1">Total CT Scans</p>
                    <h3 class="text-3xl font-bold text-slate-800">{{ $systemStats['total_scans'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center text-white shadow-lg shadow-cyan-500/30">
                    <i data-lucide="scan" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-emerald-500 flex items-center font-medium"><i data-lucide="trending-up" class="w-4 h-4 mr-1"></i> AI Active</span>
                <span class="text-slate-400 ml-2">100% processing</span>
            </div>
        </div>

        <!-- Stat Card 3 -->
        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow group relative overflow-hidden">
            <div class="absolute right-[-10px] top-[-10px] bg-indigo-50 w-24 h-24 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm font-semibold text-slate-500 mb-1">Health Records</p>
                    <h3 class="text-3xl font-bold text-slate-800">{{ $systemStats['total_records'] }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                    <i data-lucide="file-text" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-blue-500 flex items-center font-medium"><i data-lucide="activity" class="w-4 h-4 mr-1"></i> Synced</span>
                <span class="text-slate-400 ml-2">latest clinical data</span>
            </div>
        </div>

        <!-- Stat Card 4 -->
        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-shadow group relative overflow-hidden">
            <div class="absolute right-[-10px] top-[-10px] bg-amber-50 w-24 h-24 rounded-full opacity-50 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <p class="text-sm font-semibold text-slate-500 mb-1">Registered Patients</p>
                    @php $patientCount = collect($roleDistribution)->firstWhere('name', 'Patient')['value'] ?? 0; @endphp
                    <h3 class="text-3xl font-bold text-slate-800">{{ $patientCount }}</h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-lg shadow-amber-500/30">
                    <i data-lucide="heart-pulse" class="w-6 h-6"></i>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm w-full bg-slate-100 rounded-full h-1.5 mt-4">
                <div class="bg-amber-500 h-1.5 rounded-full" style="width: 70%"></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Patient Growth Chart -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 lg:col-span-2" x-data="chartFilter()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">Patient Onboarding Growth</h3>
                <div class="relative">
                    <select x-model="filter" @change="updateChart()" class="appearance-none bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg px-4 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>
            </div>
            
            <div class="relative w-full h-[300px]">
                <div x-show="loading" x-transition class="absolute inset-0 z-10 bg-white/80 backdrop-blur-sm flex items-center justify-center rounded-xl">
                    <div class="flex flex-col items-center">
                        <i data-lucide="loader-2" class="w-8 h-8 text-blue-500 animate-spin mb-2"></i>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Loading Data...</span>
                    </div>
                </div>
                <div id="patientGrowthChart" class="w-full h-full"></div>
            </div>
        </div>

        <!-- Role Distribution Chart -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800">User Distribution</h3>
            </div>
            <div id="roleDistributionChart" class="w-full h-[300px] flex items-center justify-center"></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-6">Quick Actions</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <a href="{{ route('admin.users.patients') }}" class="flex flex-col items-center p-6 rounded-2xl border border-slate-100 bg-slate-50 hover:border-blue-300 hover:bg-blue-50 hover:shadow-lg hover:shadow-blue-500/10 transition-all group">
                <div class="w-14 h-14 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i data-lucide="users" class="w-7 h-7"></i>
                </div>
                <span class="text-sm font-bold text-slate-700">Manage Patients</span>
            </a>
            
            <a href="{{ route('admin.users.doctors') }}" class="flex flex-col items-center p-6 rounded-2xl border border-slate-100 bg-slate-50 hover:border-indigo-300 hover:bg-indigo-50 hover:shadow-lg hover:shadow-indigo-500/10 transition-all group">
                <div class="w-14 h-14 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i data-lucide="stethoscope" class="w-7 h-7"></i>
                </div>
                <span class="text-sm font-bold text-slate-700">Manage Doctors</span>
            </a>

            <a href="{{ route('admin.users.medrec') }}" class="flex flex-col items-center p-6 rounded-2xl border border-slate-100 bg-slate-50 hover:border-teal-300 hover:bg-teal-50 hover:shadow-lg hover:shadow-teal-500/10 transition-all group">
                <div class="w-14 h-14 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i data-lucide="clipboard-list" class="w-7 h-7"></i>
                </div>
                <span class="text-sm font-bold text-slate-700">Medrec Staff</span>
            </a>

            <a href="{{ route('admin.settings') }}" class="flex flex-col items-center p-6 rounded-2xl border border-slate-100 bg-slate-50 hover:border-purple-300 hover:bg-purple-50 hover:shadow-lg hover:shadow-purple-500/10 transition-all group">
                <div class="w-14 h-14 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i data-lucide="settings" class="w-7 h-7"></i>
                </div>
                <span class="text-sm font-bold text-slate-700">Settings</span>
            </a>

            <a href="{{ route('admin.site-config') }}" class="flex flex-col items-center p-6 rounded-2xl border border-slate-100 bg-slate-50 hover:border-rose-300 hover:bg-rose-50 hover:shadow-lg hover:shadow-rose-500/10 transition-all group">
                <div class="w-14 h-14 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i data-lucide="sliders-horizontal" class="w-7 h-7"></i>
                </div>
                <span class="text-sm font-bold text-slate-700">Site Config</span>
            </a>
        </div>
    </div>
</div>

<script>
    let rawGrowthData = (@json($patientGrowth)) || [];

    window.chartFilter = () => {
        return {
            filter: 'weekly',
            loading: false,
            chart: null,
            init() {
                this.renderChart(this.processData('weekly'));
            },
            updateChart() {
                this.loading = true;
                setTimeout(() => {
                    const data = this.processData(this.filter);
                    this.chart.updateSeries([{ data: data.values }]);
                    this.chart.updateOptions({ xaxis: { categories: data.labels } });
                    this.loading = false;
                }, 300); // smooth animation simulation
            },
            processData(type) {
                let labels = [];
                let values = [];
                
                if (type === 'weekly') {
                    const recent = rawGrowthData.slice(-7);
                    labels = recent.map(d => new Date(d.date).toLocaleDateString('en-US', { weekday: 'short' }));
                    values = recent.map(d => d.patients);
                } else if (type === 'monthly') {
                    labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                    values = [
                        rawGrowthData.slice(-28, -21).reduce((a,b)=>a+b.patients,0) || 0,
                        rawGrowthData.slice(-21, -14).reduce((a,b)=>a+b.patients,0) || 0,
                        rawGrowthData.slice(-14, -7).reduce((a,b)=>a+b.patients,0) || 0,
                        rawGrowthData.slice(-7).reduce((a,b)=>a+b.patients,0) || 0
                    ];
                } else {
                    labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    values = Array(12).fill(0);
                    rawGrowthData.forEach(d => {
                        const m = new Date(d.date).getMonth();
                        values[m] += d.patients;
                    });
                }
                
                return { labels, values };
            },
            renderChart(data) {
                var optionsGrowth = {
                    series: [{ name: 'New Patients', data: data.values }],
                    chart: { 
                        type: 'area', 
                        height: 320, 
                        fontFamily: 'Inter, sans-serif', 
                        toolbar: { show: false },
                        animations: { enabled: true, easing: 'easeinout', speed: 800 }
                    },
                    colors: ['#0ea5e9'],
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.05, stops: [0, 90, 100] } },
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 3, colors: ['#0ea5e9'] },
                    xaxis: { categories: data.labels, axisBorder: { show: true, color: '#cbd5e1' }, axisTicks: { show: true, color: '#cbd5e1' } },
                    yaxis: { show: true, axisBorder: { show: true, color: '#cbd5e1' }, axisTicks: { show: true, color: '#cbd5e1' } },
                    tooltip: { theme: 'light', marker: { show: true }, y: { formatter: function (val) { return val + " patients" } } },
                    grid: { borderColor: '#f1f5f9', strokeDashArray: 4, yaxis: { lines: { show: true } } }
                };
                this.chart = new ApexCharts(document.querySelector("#patientGrowthChart"), optionsGrowth);
                this.chart.render();
            }
        }
    };

    // Role Distribution Chart
    let roleData = @json($roleDistribution);
    
    document.addEventListener('DOMContentLoaded', function() {
        let optionsRole = {
            series: roleData.map(d => d.value),
            labels: roleData.map(d => d.name),
            chart: {
                type: 'donut',
                height: 340,
                fontFamily: 'Inter, sans-serif',
            },
            colors: ['#0ea5e9', '#3b82f6', '#8b5cf6', '#f59e0b'],
            plotOptions: { 
                pie: { 
                    donut: { 
                        size: '75%', 
                        labels: { 
                            show: true, 
                            name: { show: true, fontSize: '14px', color: '#64748b' }, 
                            value: { show: true, fontSize: '24px', fontWeight: 700, color: '#1e293b' } 
                        } 
                    } 
                } 
            },
            dataLabels: { enabled: false },
            legend: { position: 'bottom', markers: { radius: 12 } },
            stroke: { show: false }
        };

        if (document.querySelector("#roleDistributionChart")) {
            new ApexCharts(document.querySelector("#roleDistributionChart"), optionsRole).render();
        }
    });
</script>
@endsection
