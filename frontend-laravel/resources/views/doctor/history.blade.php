@extends('layouts.dashboard')

@section('title', 'Examination History')

@section('dashboard_content')
<div class="max-w-7xl mx-auto space-y-6" x-data="doctorHistory()">

    <!-- Header -->
    <div class="bg-white rounded-3xl p-6 lg:p-8 border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-80 h-80 bg-gradient-to-bl from-indigo-50/70 via-transparent to-transparent rounded-bl-full pointer-events-none opacity-60"></div>
        <div class="relative z-10 flex items-center space-x-5">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30">
                <i data-lucide="history" class="w-7 h-7"></i>
            </div>
            <div>
                <h2 class="text-2xl font-black text-slate-800 tracking-tight">Examination History</h2>
                <p class="text-xs text-slate-500 mt-1">A full log of CT scans you have reviewed, their AI predictions, and your final verdicts.</p>
            </div>
        </div>
        <div class="relative z-10 flex items-center space-x-3">
            <div class="px-4 py-2.5 bg-indigo-50 text-indigo-700 rounded-2xl border border-indigo-100 flex items-center font-bold text-xs">
                <i data-lucide="file-check" class="w-4 h-4 mr-2"></i>
                {{ count($historyScans) }} Total Reviewed
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl shadow-sm flex items-start space-x-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 mt-0.5"></i>
            <div class="flex-1 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        </div>
    @endif

    <!-- Toolbar -->
    <div class="bg-white p-4 rounded-3xl border border-slate-100 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-4">
        <!-- Tabs -->
        <div class="flex bg-slate-100 p-1 rounded-2xl w-full lg:w-auto overflow-x-auto">
            <button @click="activeTab = 'all'" :class="activeTab == 'all' ? 'bg-white text-slate-800 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'" class="flex-1 lg:flex-none px-5 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-2 whitespace-nowrap">
                <i data-lucide="layers" class="w-4 h-4"></i>
                <span>All (<span x-text="scans.length"></span>)</span>
            </button>
            <button @click="activeTab = 'approved'" :class="activeTab == 'approved' ? 'bg-white text-emerald-600 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'" class="flex-1 lg:flex-none px-5 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-2 whitespace-nowrap">
                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                <span>Approved (<span x-text="approvedCount()"></span>)</span>
            </button>
            <button @click="activeTab = 'rejected'" :class="activeTab == 'rejected' ? 'bg-white text-rose-600 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'" class="flex-1 lg:flex-none px-5 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-2 whitespace-nowrap">
                <i data-lucide="x-circle" class="w-4 h-4"></i>
                <span>Rejected (<span x-text="rejectedCount()"></span>)</span>
            </button>
        </div>
        <!-- Search -->
        <div class="relative flex-1 lg:w-72">
            <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-slate-400"></i>
            <input type="text" x-model="searchQuery" placeholder="Search patient name..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 outline-none transition-all placeholder:text-slate-400">
        </div>
    </div>

    <!-- History Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-500 uppercase tracking-wider">Scan Date</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-500 uppercase tracking-wider">AI Prediction</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-500 uppercase tracking-wider">Risk</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-500 uppercase tracking-wider">Confidence</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-500 uppercase tracking-wider">Your Verdict</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-500 uppercase tracking-wider">Diagnosis Notes</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="scan in filteredScans()" :key="scan.id">
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <!-- Patient -->
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 text-indigo-700 flex items-center justify-center font-black text-sm flex-shrink-0 border border-indigo-200/50 shadow-inner">
                                        <span x-text="(scan.patient && scan.patient.user) ? scan.patient.user.full_name.charAt(0).toUpperCase() : 'U'"></span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 text-xs leading-tight" x-text="(scan.patient && scan.patient.user) ? scan.patient.user.full_name : 'Unknown Patient'"></p>
                                        <p class="text-[10px] text-slate-400 font-semibold mt-0.5" x-text="'Scan #' + scan.id"></p>
                                    </div>
                                </div>
                            </td>
                            <!-- Date -->
                            <td class="px-6 py-4">
                                <span class="text-xs font-semibold text-slate-600" x-text="formatDate(scan.created_at)"></span>
                            </td>
                            <!-- AI Prediction -->
                            <td class="px-6 py-4 max-w-[160px]">
                                <template x-if="scan.ai_result">
                                    <div>
                                        <p class="text-xs font-black text-slate-800 truncate" x-text="scan.ai_result.prediction_label"></p>
                                        <p class="text-[10px] text-slate-400 mt-0.5 line-clamp-1" x-text="scan.ai_result.result_text"></p>
                                    </div>
                                </template>
                                <template x-if="!scan.ai_result">
                                    <span class="text-[10px] text-slate-400 italic">No AI result</span>
                                </template>
                            </td>
                            <!-- Risk Level -->
                            <td class="px-6 py-4">
                                <template x-if="scan.ai_result && scan.ai_result.risk_level === 'High'">
                                    <span class="px-2 py-1 bg-rose-50 text-rose-700 border border-rose-200 text-[9px] font-black uppercase rounded-lg">High</span>
                                </template>
                                <template x-if="scan.ai_result && scan.ai_result.risk_level === 'Medium'">
                                    <span class="px-2 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-[9px] font-black uppercase rounded-lg">Medium</span>
                                </template>
                                <template x-if="scan.ai_result && scan.ai_result.risk_level === 'Low'">
                                    <span class="px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-black uppercase rounded-lg">Low</span>
                                </template>
                                <template x-if="!scan.ai_result || !scan.ai_result.risk_level">
                                    <span class="text-[10px] text-slate-400 italic">—</span>
                                </template>
                            </td>
                            <!-- Confidence -->
                            <td class="px-6 py-4">
                                <template x-if="scan.ai_result">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full bg-indigo-500" :style="'width:' + (scan.ai_result.confidence * 100).toFixed(0) + '%'"></div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-600" x-text="(scan.ai_result.confidence * 100).toFixed(1) + '%'"></span>
                                    </div>
                                </template>
                                <template x-if="!scan.ai_result">
                                    <span class="text-[10px] text-slate-400 italic">—</span>
                                </template>
                            </td>
                            <!-- Verdict -->
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border inline-block"
                                      :class="scan.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                      x-text="scan.status === 'approved' ? '✓ Approved' : '✗ Rejected'"></span>
                            </td>
                            <!-- Diagnosis Notes -->
                            <td class="px-6 py-4 max-w-[200px]">
                                <template x-if="scan.diagnosis && scan.diagnosis.notes">
                                    <p class="text-xs text-slate-600 line-clamp-2" x-text="scan.diagnosis.notes"></p>
                                </template>
                                <template x-if="!scan.diagnosis || !scan.diagnosis.notes">
                                    <span class="text-[10px] text-slate-400 italic">No notes recorded</span>
                                </template>
                            </td>
                            <!-- Action -->
                            <td class="px-6 py-4">
                                <a :href="'/doctor/scans/' + scan.id + '/review'" class="inline-flex items-center px-3 py-1.5 bg-slate-50 hover:bg-indigo-600 text-slate-600 hover:text-white border border-slate-200 hover:border-indigo-600 rounded-xl text-[10px] font-bold transition-all">
                                    <i data-lucide="eye" class="w-3.5 h-3.5 mr-1"></i> View
                                </a>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty Row -->
                    <template x-if="filteredScans().length === 0">
                        <tr>
                            <td colspan="8" class="py-20 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <i data-lucide="file-search" class="w-12 h-12 mb-3 text-slate-200"></i>
                                    <p class="text-sm font-bold text-slate-500">No examination records found</p>
                                    <p class="text-xs text-slate-400 mt-1">Records will appear here once you review CT scans.</p>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Pagination -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] text-slate-500 font-semibold">
                Showing <span class="font-black text-slate-700" x-text="filteredScans().length"></span> of <span class="font-black text-slate-700" x-text="scans.length"></span> records
            </span>
            <div class="flex items-center space-x-2" x-show="totalPages() > 1">
                <button @click="prevPage()" :disabled="currentPage == 1" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                    ← Prev
                </button>
                <span class="text-[11px] font-semibold text-slate-500">
                    Page <span class="font-black text-slate-800" x-text="currentPage"></span> of <span class="font-black text-slate-800" x-text="totalPages()"></span>
                </span>
                <button @click="nextPage()" :disabled="currentPage == totalPages()" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-[11px] font-bold text-slate-600 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                    Next →
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function doctorHistory() {
    return {
        scans: @json(array_values($historyScans ?? [])),
        activeTab: 'all',
        searchQuery: '',
        currentPage: 1,
        perPage: 15,

        init() {
            this.$watch('activeTab', () => { this.currentPage = 1; setTimeout(() => lucide.createIcons(), 50); });
            this.$watch('searchQuery', () => { this.currentPage = 1; });
            setTimeout(() => lucide.createIcons(), 100);
        },

        approvedCount() {
            return this.scans.filter(s => s.status === 'approved').length;
        },
        rejectedCount() {
            return this.scans.filter(s => s.status === 'rejected').length;
        },

        filteredScans() {
            let result = this.scans.filter(s => {
                let matchTab = true;
                if (this.activeTab === 'approved') matchTab = s.status === 'approved';
                else if (this.activeTab === 'rejected') matchTab = s.status === 'rejected';

                let matchSearch = true;
                if (this.searchQuery.trim() !== '') {
                    let q = this.searchQuery.toLowerCase();
                    let name = (s.patient && s.patient.user && s.patient.user.full_name) ? s.patient.user.full_name.toLowerCase() : '';
                    matchSearch = name.includes(q) || String(s.id).includes(q);
                }

                return matchTab && matchSearch;
            });
            // Sort newest first
            result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            return result;
        },

        paginatedScans() {
            let start = (this.currentPage - 1) * this.perPage;
            return this.filteredScans().slice(start, start + this.perPage);
        },

        totalPages() {
            return Math.ceil(this.filteredScans().length / this.perPage) || 1;
        },

        prevPage() { if (this.currentPage > 1) this.currentPage--; },
        nextPage() { if (this.currentPage < this.totalPages()) this.currentPage++; },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        }
    }
}
</script>
@endsection
