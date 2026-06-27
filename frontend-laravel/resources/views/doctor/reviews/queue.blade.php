@extends('layouts.dashboard')

@section('title', 'Doctor Review Queue')

@section('dashboard_content')
<div class="max-w-7xl mx-auto space-y-6" x-data="reviewQueue()">
    <!-- Header & Summary Cards -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Diagnostic Review Queue</h2>
            <p class="text-slate-500 text-sm mt-1">Manage, analyze, and validate AI pre-diagnosed CT scans.</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="bg-white px-4 py-2 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-2 text-xs font-bold text-slate-600">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                <span><span x-text="pendingCount()"></span> Pending Validation</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl shadow-sm flex items-start space-x-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 mt-0.5"></i>
            <div class="flex-1 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 p-4 rounded-2xl shadow-sm flex items-start space-x-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 mt-0.5"></i>
            <div class="flex-1 text-sm font-medium text-red-800">{{ session('error') }}</div>
        </div>
    @endif

    <!-- Toolbar: Tabs, Search, Sort -->
    <div class="bg-white p-4 rounded-3xl border border-slate-100 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-4">
        <!-- Tabs -->
        <div class="flex bg-slate-100 p-1 rounded-2xl w-full lg:w-auto overflow-x-auto custom-scrollbar">
            <button @click="activeTab = 'pending'" :class="activeTab == 'pending' ? 'bg-white text-blue-600 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'" class="flex-1 lg:flex-none px-5 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-2 whitespace-nowrap">
                <i data-lucide="clock" class="w-4 h-4"></i>
                <span>Pending (<span x-text="pendingCount()"></span>)</span>
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

        <!-- Search & Sort -->
        <div class="flex items-center space-x-3 w-full lg:w-auto">
            <div class="relative flex-1 lg:w-64">
                <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-slate-400"></i>
                <input type="text" x-model="searchQuery" placeholder="Search patient name or ID..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all placeholder:text-slate-400">
            </div>
            <select x-model="sortBy" class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="urgency">Urgency Level</option>
            </select>
        </div>
    </div>

    <!-- Scans Grid/List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="scan in paginatedScans()" :key="scan.id">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group">
                <!-- Card Header / Image Preview -->
                <div class="relative h-48 bg-slate-950 overflow-hidden flex items-center justify-center">
                    <img :src="scan.image_url && scan.image_url.startsWith('http') ? scan.image_url : '{{ config('services.go_api.url', 'http://localhost:8080') }}/' + scan.image_url" alt="CT Scan" class="absolute inset-0 w-full h-full object-cover opacity-80 group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent"></div>
                    
                    <!-- Urgency / Status Badges -->
                    <div class="absolute top-4 left-4 flex flex-col space-y-1 z-10">
                        <template x-if="['pending_review', 'uploaded', 'pending', 'ai_processing'].includes(scan.status.toLowerCase())">
                            <span class="px-2.5 py-1 bg-amber-500/90 backdrop-blur-md text-white text-[10px] font-black uppercase tracking-widest rounded-lg border border-amber-400/30 shadow-sm flex items-center">
                                <i data-lucide="alert-circle" class="w-3 h-3 mr-1"></i> Needs Review
                            </span>
                        </template>
                        <template x-if="scan.status == 'approved'">
                            <span class="px-2.5 py-1 bg-emerald-500/90 backdrop-blur-md text-white text-[10px] font-black uppercase tracking-widest rounded-lg border border-emerald-400/30 shadow-sm flex items-center">
                                <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> Approved
                            </span>
                        </template>
                        <template x-if="scan.status == 'rejected'">
                            <span class="px-2.5 py-1 bg-rose-500/90 backdrop-blur-md text-white text-[10px] font-black uppercase tracking-widest rounded-lg border border-rose-400/30 shadow-sm flex items-center">
                                <i data-lucide="x-circle" class="w-3 h-3 mr-1"></i> Rejected
                            </span>
                        </template>
                    </div>

                    <!-- Confidence Score Badge -->
                    <template x-if="scan.ai_result">
                        <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-md px-2.5 py-1 rounded-lg border border-white/20 shadow-sm flex items-center space-x-1">
                            <i data-lucide="brain" class="w-3.5 h-3.5 text-blue-600"></i>
                            <span class="text-xs font-black text-slate-800" x-text="numberFormat(scan.ai_result.confidence * 100) + '%'"></span>
                        </div>
                    </template>
                </div>

                <!-- Card Body -->
                <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                    <div>
                        <!-- Patient Info -->
                        <div class="flex items-center space-x-3 mb-3">
                            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-base shadow-inner border border-blue-100 flex-shrink-0">
                                <span x-text="(scan.patient && scan.patient.user) ? scan.patient.user.full_name.substring(0,1) : 'U'"></span>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="text-base font-bold text-slate-900 truncate" x-text="(scan.patient && scan.patient.user) ? scan.patient.user.full_name : 'Unknown Patient'"></h4>
                                <div class="flex items-center text-xs text-slate-500 space-x-2 mt-0.5">
                                    <span class="flex items-center"><i data-lucide="hash" class="w-3 h-3 mr-0.5 text-slate-400"></i> <span x-text="scan.id"></span></span>
                                    <span>•</span>
                                    <span class="flex items-center"><i data-lucide="calendar" class="w-3 h-3 mr-1 text-slate-400"></i> <span x-text="formatDate(scan.created_at)"></span></span>
                                </div>
                            </div>
                        </div>

                        <!-- AI Prediction Detail -->
                        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 space-y-1">
                            <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <span>AI Prediction</span>
                                <template x-if="scan.ai_result && scan.ai_result.risk_level == 'High'">
                                    <span class="text-rose-600 font-extrabold flex items-center"><i data-lucide="flame" class="w-3 h-3 mr-0.5"></i> HIGH RISK</span>
                                </template>
                                <template x-if="scan.ai_result && scan.ai_result.risk_level == 'Medium'">
                                    <span class="text-amber-600 font-extrabold flex items-center">MEDIUM RISK</span>
                                </template>
                                <template x-if="scan.ai_result && scan.ai_result.risk_level == 'Low'">
                                    <span class="text-emerald-600 font-extrabold flex items-center">LOW RISK</span>
                                </template>
                            </div>
                            <p class="text-sm font-black text-slate-800" x-text="scan.ai_result ? scan.ai_result.prediction_label : 'Pending AI Analysis'"></p>
                            <p class="text-xs text-slate-500 line-clamp-2" x-text="scan.ai_result ? scan.ai_result.result_text : 'No detailed analysis text available.'"></p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-2 border-t border-slate-100">
                        <a :href="'/doctor/scans/' + scan.id + '/review'" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all flex items-center justify-center space-x-2 group">
                            <span x-text="['approved', 'rejected'].includes((scan.status || '').toLowerCase()) ? 'View Examination Details' : 'Perform Clinical Review'"></span>
                            <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="filteredScans().length === 0">
        <div class="bg-white rounded-3xl border border-slate-100 p-16 text-center shadow-sm">
            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-dashed border-slate-200">
                <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
            </div>
            <h4 class="text-base font-bold text-slate-700 mb-1">No Scans Found</h4>
            <p class="text-xs text-slate-500 max-w-xs mx-auto">There are no CT scans matching your current filter or search criteria.</p>
        </div>
    </template>

    <!-- Pagination -->
    <template x-if="totalPages() > 1">
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
            <button @click="prevPage()" :disabled="currentPage == 1" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:pointer-events-none transition-all shadow-sm">
                Previous
            </button>
            <span class="text-xs font-semibold text-slate-500">Page <span x-text="currentPage" class="font-bold text-slate-800"></span> of <span x-text="totalPages()" class="font-bold text-slate-800"></span></span>
            <button @click="nextPage()" :disabled="currentPage == totalPages()" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:pointer-events-none transition-all shadow-sm">
                Next
            </button>
        </div>
    </template>
</div>

<script>
function reviewQueue() {
    return {
        scans: @json($scans ?? []),
        activeTab: 'pending',
        searchQuery: '',
        sortBy: 'oldest',
        currentPage: 1,
        perPage: 6,

        init() {
            // Re-init lucide icons on updates
            this.$watch('activeTab', () => { this.currentPage = 1; setTimeout(() => lucide.createIcons(), 50); });
            this.$watch('searchQuery', () => { this.currentPage = 1; setTimeout(() => lucide.createIcons(), 50); });
            this.$watch('sortBy', () => { this.currentPage = 1; setTimeout(() => lucide.createIcons(), 50); });
        },

        pendingCount() {
            return this.scans.filter(s => ['pending_review', 'uploaded', 'pending', 'ai_processing'].includes((s.status || '').toLowerCase())).length;
        },
        approvedCount() {
            return this.scans.filter(s => s.status == 'approved').length;
        },
        rejectedCount() {
            return this.scans.filter(s => s.status == 'rejected').length;
        },

        filteredScans() {
            let result = this.scans.filter(s => {
                let matchTab = false;
                let stat = (s.status || '').toLowerCase();
                if (this.activeTab == 'pending') matchTab = ['pending_review', 'uploaded', 'pending', 'ai_processing'].includes(stat);
                else if (this.activeTab == 'approved') matchTab = (stat == 'approved');
                else if (this.activeTab == 'rejected') matchTab = (stat == 'rejected');

                let matchSearch = true;
                if (this.searchQuery.trim() !== '') {
                    let q = this.searchQuery.toLowerCase();
                    let name = (s.patient && s.patient.user && s.patient.user.full_name) ? s.patient.user.full_name.toLowerCase() : '';
                    let idStr = s.id ? s.id.toString() : '';
                    matchSearch = name.includes(q) || idStr.includes(q);
                }

                return matchTab && matchSearch;
            });

            // Sorting
            result.sort((a, b) => {
                if (this.sortBy == 'newest') return new Date(b.created_at) - new Date(a.created_at);
                if (this.sortBy == 'oldest') return new Date(a.created_at) - new Date(b.created_at);
                if (this.sortBy == 'urgency') {
                    let riskA = a.ai_result ? (a.ai_result.risk_level == 'High' ? 3 : (a.ai_result.risk_level == 'Medium' ? 2 : 1)) : 0;
                    let riskB = b.ai_result ? (b.ai_result.risk_level == 'High' ? 3 : (b.ai_result.risk_level == 'Medium' ? 2 : 1)) : 0;
                    return riskB - riskA;
                }
                return 0;
            });

            return result;
        },

        totalPages() {
            return Math.ceil(this.filteredScans().length / this.perPage) || 1;
        },

        paginatedScans() {
            let start = (this.currentPage - 1) * this.perPage;
            return this.filteredScans().slice(start, start + this.perPage);
        },

        prevPage() {
            if (this.currentPage > 1) this.currentPage--;
            setTimeout(() => lucide.createIcons(), 50);
        },

        nextPage() {
            if (this.currentPage < this.totalPages()) this.currentPage++;
            setTimeout(() => lucide.createIcons(), 50);
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        numberFormat(val) {
            return parseFloat(val).toFixed(1);
        }
    }
}
</script>
@endsection
