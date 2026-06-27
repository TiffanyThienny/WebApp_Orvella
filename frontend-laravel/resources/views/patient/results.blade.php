@extends('layouts.dashboard')

@section('title', 'My Clinical Results')

@section('dashboard_content')
<div class="max-w-6xl mx-auto space-y-6" x-data="resultsPage()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">My Diagnostic Reports</h2>
            <p class="text-slate-500 text-sm mt-1">Scan results reviewed and signed off by your specialist physician.</p>
        </div>
        <div class="flex items-center space-x-2 text-sm">
            <span class="flex items-center text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-xl font-semibold border border-emerald-100">
                <i data-lucide="shield-check" class="w-4 h-4 mr-1.5"></i> Clinically Verified
            </span>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-col sm:flex-row gap-3 items-center">
        <!-- Search -->
        <div class="relative flex-1 w-full">
            <i data-lucide="search" class="absolute left-3.5 top-3 w-4 h-4 text-slate-400 pointer-events-none"></i>
            <input type="text" x-model="searchQuery" placeholder="Search by doctor name or report ID..."
                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all placeholder:text-slate-400">
        </div>
        <!-- Filter by status/date -->
        <select x-model="sortBy" class="px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-600 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
            <option value="newest">Newest First</option>
            <option value="oldest">Oldest First</option>
        </select>
        <!-- Count badge -->
        <span class="flex-shrink-0 px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-bold rounded-xl border border-blue-100" x-text="filteredResults().length + ' Report(s)'"></span>
    </div>

    <!-- Results List (Accordion) -->
    <div class="space-y-3">
        <template x-for="(scan, index) in paginatedResults()" :key="scan.id">
            <div :id="'report-card-' + scan.id" class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <!-- Summary Row (always visible, clickable to expand/collapse) -->
                <div class="p-5 flex flex-col sm:flex-row sm:items-center gap-4 cursor-pointer select-none"
                     @click="toggleExpand(scan.id)">
                    <div class="flex items-center space-x-4 flex-1">
                        <!-- Icon -->
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 border border-blue-100">
                            <i data-lucide="file-check-2" class="w-6 h-6"></i>
                        </div>
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h4 class="text-base font-bold text-slate-900">Diagnostic Report</h4>
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-500 text-[10px] font-bold rounded uppercase tracking-widest"
                                      x-text="'#' + String(scan.id).padStart(5, '0')"></span>
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded-lg border border-emerald-100">Approved</span>
                            </div>
                            <div class="flex flex-wrap items-center text-xs text-slate-500 gap-3">
                                <span class="flex items-center">
                                     <i data-lucide="user" class="w-3.5 h-3.5 mr-1 text-slate-400"></i>
                                     <span x-text="scan.doctor ? 'Dr. ' + (scan.doctor.full_name || 'Specialist') : 'Specialist'"></span>
                                </span>
                                <span class="flex items-center">
                                     <i data-lucide="calendar" class="w-3.5 h-3.5 mr-1 text-slate-400"></i>
                                     <span x-text="formatDate(scan.created_at)"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Right: Download PDF & Chevron indicator -->
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button @click.stop="downloadReportPDF(scan, 'report-card-' + scan.id)" class="no-print inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm gap-1.5">
                            <i data-lucide="file-down" class="w-4 h-4"></i> Download PDF
                        </button>
                        <div class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200/60 flex items-center justify-center text-slate-400 transition-transform duration-200"
                             :class="expandedId === scan.id ? 'rotate-180 text-blue-600 border-blue-200 bg-blue-50/20' : ''">
                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <!-- Expanded Content (Accordion Panel) -->
                <div x-show="expandedId === scan.id"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="border-t border-slate-100 bg-slate-50/30 p-6 space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- CT Scan Image View -->
                        <div class="lg:col-span-5 space-y-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Medical Imaging Preview</span>
                            <div class="border border-slate-200 rounded-xl overflow-hidden bg-slate-950 aspect-square flex items-center justify-center p-2">
                                <img :src="scan.ai_result && scan.ai_result.analyzed_image_url ? (scan.ai_result.analyzed_image_url.startsWith('http') ? scan.ai_result.analyzed_image_url : 'http://localhost:8080/' + scan.ai_result.analyzed_image_url) : (scan.image_url.startsWith('http') ? scan.image_url : 'http://localhost:8080/' + scan.image_url)" alt="CT Scan" class="max-w-full max-h-[300px] object-contain">
                            </div>
                            <span class="text-[10px] text-center text-slate-400 block italic" x-text="scan.ai_result && scan.ai_result.analyzed_image_url ? 'Side-by-Side Comparison Layout' : 'Original CT Scan File'"></span>
                        </div>

                        <!-- Findings and Prescription recommendations -->
                        <div class="lg:col-span-7 space-y-4">
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Specialist Radiologist Notes</span>
                                <div class="bg-white p-4 rounded-xl border border-slate-200/60 text-sm text-slate-700 leading-relaxed italic relative">
                                    <span class="absolute top-1 left-2 text-slate-200 font-serif text-3xl">"</span>
                                    <span class="relative z-10 pl-3" x-text="parseMedicalNotes(scan).medical_notes || 'No specialist findings recorded.'"></span>
                                    <span class="absolute bottom-1 right-2 text-slate-200 font-serif text-3xl">"</span>
                                </div>
                            </div>

                            <!-- Prescription parameters Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="hasRecommendations(scan)">
                                <template x-if="parseMedicalNotes(scan).food_allowed">
                                    <div class="bg-emerald-50/40 p-3.5 rounded-xl border border-emerald-100 text-xs">
                                        <h5 class="font-extrabold text-emerald-800 uppercase tracking-wider mb-1 flex items-center"><i data-lucide="check-circle" class="w-3.5 h-3.5 mr-1 text-emerald-600"></i> Foods Allowed</h5>
                                        <p class="text-slate-600" x-text="parseMedicalNotes(scan).food_allowed"></p>
                                    </div>
                                </template>

                                <template x-if="parseMedicalNotes(scan).food_avoided">
                                    <div class="bg-rose-50/40 p-3.5 rounded-xl border border-rose-100 text-xs">
                                        <h5 class="font-extrabold text-rose-800 uppercase tracking-wider mb-1 flex items-center"><i data-lucide="x-circle" class="w-3.5 h-3.5 mr-1 text-rose-600"></i> Foods to Avoid</h5>
                                        <p class="text-slate-600" x-text="parseMedicalNotes(scan).food_avoided"></p>
                                    </div>
                                </template>

                                <template x-if="parseMedicalNotes(scan).recommended_activities">
                                    <div class="bg-blue-50/40 p-3.5 rounded-xl border border-blue-100 text-xs">
                                        <h5 class="font-extrabold text-blue-800 uppercase tracking-wider mb-1 flex items-center"><i data-lucide="dumbbell" class="w-3.5 h-3.5 mr-1 text-blue-600"></i> Recommended Activities</h5>
                                        <p class="text-slate-600" x-text="parseMedicalNotes(scan).recommended_activities"></p>
                                    </div>
                                </template>

                                <template x-if="parseMedicalNotes(scan).avoided_activities">
                                    <div class="bg-amber-50/40 p-3.5 rounded-xl border border-amber-100 text-xs">
                                        <h5 class="font-extrabold text-amber-800 uppercase tracking-wider mb-1 flex items-center"><i data-lucide="alert-triangle" class="w-3.5 h-3.5 mr-1 text-amber-600"></i> Activities to Avoid</h5>
                                        <p class="text-slate-600" x-text="parseMedicalNotes(scan).avoided_activities"></p>
                                    </div>
                                </template>
                            </div>

                            <template x-if="parseMedicalNotes(scan).lifestyle_recommendations">
                                <div class="bg-purple-50/40 p-3.5 rounded-xl border border-purple-100 text-xs">
                                    <h5 class="font-extrabold text-purple-800 uppercase tracking-wider mb-1 flex items-center"><i data-lucide="heart" class="w-3.5 h-3.5 mr-1 text-purple-650"></i> Lifestyle Recommendations</h5>
                                    <p class="text-slate-600" x-text="parseMedicalNotes(scan).lifestyle_recommendations"></p>
                                </div>
                            </template>

                            <template x-if="parseMedicalNotes(scan).next_checkup">
                                <div class="bg-indigo-50 border border-indigo-150 p-4 rounded-xl flex items-center justify-between text-xs">
                                    <div class="flex items-center space-x-2">
                                        <i data-lucide="calendar" class="w-4 h-4 text-indigo-600"></i>
                                        <div>
                                            <span class="block font-bold text-slate-500 uppercase tracking-widest text-[9px]">Follow-up Appointment</span>
                                            <span class="text-slate-850 font-extrabold" x-text="parseMedicalNotes(scan).next_checkup"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Footer of panel -->
                    <div class="border-t border-slate-100 pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/40 -mx-6 -mb-6 p-6">
                        <div class="flex items-center space-x-2 text-xs text-slate-400">
                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Clinically verified diagnostics system. Validated and signed off.</span>
                        </div>
                        <button @click.stop="downloadReportPDF(scan, 'report-card-' + scan.id)" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow hover:shadow-md gap-1.5">
                            <i data-lucide="file-down" class="w-4 h-4"></i> Download Clinical PDF Report
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <template x-if="filteredResults().length === 0">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-16 text-center">
                <div class="w-20 h-20 mx-auto bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-dashed border-slate-200">
                    <i data-lucide="file-x-2" class="w-8 h-8 text-slate-300"></i>
                </div>
                <h4 class="text-lg font-bold text-slate-800 mb-2">No Reports Found</h4>
                <p class="text-sm text-slate-500 max-w-sm mx-auto leading-relaxed">
                    <template x-if="searchQuery.trim()">
                        <span>No results match your search. Try a different name or report ID.</span>
                    </template>
                    <template x-if="!searchQuery.trim()">
                        <span>Your scans are either pending review or awaiting doctor validation. Check back once your doctor has completed the review.</span>
                    </template>
                </p>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <template x-if="totalPages() > 1">
        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <button @click="prevPage()" :disabled="currentPage == 1"
                    class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:pointer-events-none transition-all shadow-sm">
                Previous
            </button>
            <span class="text-xs font-semibold text-slate-500">
                Page <span class="font-bold text-slate-800" x-text="currentPage"></span> of <span class="font-bold text-slate-800" x-text="totalPages()"></span>
            </span>
            <button @click="nextPage()" :disabled="currentPage == totalPages()"
                    class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:pointer-events-none transition-all shadow-sm">
                Next
            </button>
        </div>
    </template>
</div>

<!-- Hidden print template container (off-screen) -->
<div id="print-source" style="display:none;"></div>

<script>
async function toBase64(url) {
    try {
        const response = await fetch(url);
        const blob = await response.blob();
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    } catch (e) {
        console.warn('toBase64 conversion failed, falling back to direct URL:', e);
        return null;
    }
}

function resultsPage() {
    return {
        scans: @json($approvedResults ?? []),
        searchQuery: '',
        sortBy: 'newest',
        expandedId: null,
        currentPage: 1,
        perPage: 5,

        init() {
            this.$watch('searchQuery', () => { this.currentPage = 1; setTimeout(() => lucide.createIcons(), 50); });
            this.$watch('sortBy', () => { this.currentPage = 1; });
            this.$watch('expandedId', () => { setTimeout(() => lucide.createIcons(), 80); });
        },

        toggleExpand(id) {
            this.expandedId = this.expandedId === id ? null : id;
        },

        filteredResults() {
            let result = this.scans.filter(s => {
                if (!this.searchQuery.trim()) return true;
                const q = this.searchQuery.toLowerCase();
                const docName = (s.doctor && s.doctor.full_name) ? s.doctor.full_name.toLowerCase() : '';
                const idStr = s.id ? String(s.id) : '';
                return docName.includes(q) || idStr.includes(q);
            });

            result.sort((a, b) => {
                if (this.sortBy === 'newest') return new Date(b.created_at) - new Date(a.created_at);
                return new Date(a.created_at) - new Date(b.created_at);
            });

            return result;
        },

        totalPages() {
            return Math.ceil(this.filteredResults().length / this.perPage) || 1;
        },

        paginatedResults() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredResults().slice(start, start + this.perPage);
        },

        prevPage() { if (this.currentPage > 1) this.currentPage--; },
        nextPage() { if (this.currentPage < this.totalPages()) this.currentPage++; },

        formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        parseMedicalNotes(scan) {
            if (!scan.diagnosis || typeof scan.diagnosis !== 'object') return {};
            const raw = scan.diagnosis.notes || '';
            try {
                const parsed = JSON.parse(raw);
                return typeof parsed === 'object' ? parsed : { medical_notes: raw };
            } catch(e) {
                return { medical_notes: raw };
            }
        },

        hasRecommendations(scan) {
            const n = this.parseMedicalNotes(scan);
            return !!(n.food_allowed || n.food_avoided || n.recommended_activities || n.avoided_activities || n.lifestyle_recommendations || n.additional_notes);
        },

        async downloadReportPDF(scan, cardId) {
            if (typeof PageLoader !== 'undefined') PageLoader.start();

            // Find patient name
            const patientName = "{{ session('user')['full_name'] ?? 'Patient' }}";
            
            // Find scan count/index by finding index in sorted list
            const scanIndex = this.scans.findIndex(s => s.id === scan.id) !== -1 
                ? this.scans.length - this.scans.findIndex(s => s.id === scan.id) 
                : scan.id;

            // Extract doctor name
            const doctorName = scan.doctor ? 'Dr. ' + (scan.doctor.full_name || 'Specialist') : 'Specialist';
            
            // Parse clinical notes
            const notes = this.parseMedicalNotes(scan);
            const medicalNotes = notes.medical_notes || 'No notes available.';
            const foodAllowed = notes.food_allowed || '';
            const foodAvoided = notes.food_avoided || '';
            const recActivities = notes.recommended_activities || '';
            const avoidActivities = notes.avoided_activities || '';
            const lifestyle = notes.lifestyle_recommendations || '';
            const addNotes = notes.additional_notes || '';
            const nextCheckup = notes.next_checkup || '';

            // Embed scan image as base64 — falls back gracefully on failure
            let imageHtml = '';
            const displayImageUrl = (scan.ai_result && scan.ai_result.analyzed_image_url) ? scan.ai_result.analyzed_image_url : scan.image_url;
            if (displayImageUrl) {
                const absoluteUrl = displayImageUrl.startsWith('http') ? displayImageUrl : 'http://localhost:8080/' + displayImageUrl;
                try {
                    const b64 = await toBase64(absoluteUrl);
                    if (b64) {
                        imageHtml = `<img src="${b64}" style="width:100%;max-height:320px;object-fit:contain;border-radius:12px;border:1px solid #E2E8F0;" />`;
                    } else {
                        imageHtml = `<img src="${absoluteUrl}" style="width:100%;max-height:320px;object-fit:contain;border-radius:12px;border:1px solid #E2E8F0;" />`;
                    }
                } catch(err) {
                    imageHtml = `<img src="${absoluteUrl}" style="width:100%;max-height:320px;object-fit:contain;border-radius:12px;border:1px solid #E2E8F0;" />`;
                }
            } else {
                imageHtml = `<div style="background:#F1F5F9;color:#64748B;padding:40px;text-align:center;border-radius:12px;border:1px solid #E2E8F0;font-size:13px;font-style:italic;">CT Scan Image File</div>`;
            }

            // Construct clean, styled HTML specifically designed for print PDF
            const reportHtml = `
                <div class="p-8 space-y-6 max-w-4xl mx-auto">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b pb-6 border-slate-200">
                        <div>
                            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">ORVELLA HEALTHCARE</h1>
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mt-1">Clinical Diagnostic Report</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-slate-800">Report ID: #${String(scan.id).padStart(5, '0')}</p>
                            <p class="text-xs text-slate-500 mt-1">Date: ${this.formatDate(scan.created_at)}</p>
                        </div>
                    </div>

                    <!-- Patient & Doctor Info Grid -->
                    <div class="grid grid-cols-2 gap-6 bg-slate-50 p-5 rounded-2xl border border-slate-100">
                        <div>
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Patient Details</h3>
                            <p class="text-sm font-bold text-slate-800">${patientName}</p>
                            <p class="text-xs text-slate-500 mt-0.5">Gender: ${scan.patient && scan.patient.gender ? scan.patient.gender : 'N/A'}</p>
                        </div>
                        <div>
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Attending Specialist</h3>
                            <p class="text-sm font-bold text-slate-800">${doctorName}</p>
                            <p class="text-xs text-slate-500 mt-0.5">Orvella Specialist Panel</p>
                        </div>
                    </div>

                    <!-- Content Layout: Image Left, Findings Right -->
                    <div class="grid grid-cols-12 gap-8 my-6">
                        <!-- Left: Medical Image -->
                        <div class="col-span-5 space-y-2">
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Medical Imaging</h3>
                            <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-950 aspect-square flex items-center justify-center p-2">
                                ${imageHtml}
                            </div>
                            <p class="text-[10px] text-center text-slate-400 italic">${(scan.ai_result && scan.ai_result.analyzed_image_url) ? 'Side-by-Side Comparison' : 'Original CT Scan Image File'}</p>
                        </div>

                        <!-- Right: Clinical Findings -->
                        <div class="col-span-7 space-y-4">
                            <div>
                                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Doctor's Clinical Notes</h3>
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-sm text-slate-700 leading-relaxed italic">
                                    "${medicalNotes}"
                                </div>
                            </div>

                            ${nextCheckup ? `
                            <div class="border border-blue-100 bg-blue-50/60 p-4 rounded-xl text-xs text-slate-700">
                                <span class="font-extrabold text-blue-700 uppercase tracking-wider block mb-1">Follow-up Checkup</span>
                                <span class="font-bold text-slate-800">${nextCheckup}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Prescription & Guidelines Section -->
                    <div class="border-t pt-6 border-slate-200">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Prescription & Lifestyle Guidelines</h3>
                        <div class="grid grid-cols-2 gap-4">
                            ${foodAllowed ? `
                            <div class="bg-emerald-50/40 p-4 rounded-xl border border-emerald-100 text-xs">
                                <h4 class="font-black text-emerald-800 uppercase tracking-wider mb-1.5">Foods Allowed</h4>
                                <p class="text-slate-600 leading-relaxed">${foodAllowed}</p>
                            </div>
                            ` : ''}
                            
                            ${foodAvoided ? `
                            <div class="bg-rose-50/40 p-4 rounded-xl border border-rose-100 text-xs">
                                <h4 class="font-black text-rose-800 uppercase tracking-wider mb-1.5">Foods to Avoid</h4>
                                <p class="text-slate-600 leading-relaxed">${foodAvoided}</p>
                            </div>
                            ` : ''}

                            ${recActivities ? `
                            <div class="bg-blue-50/40 p-4 rounded-xl border border-blue-100 text-xs">
                                <h4 class="font-black text-blue-800 uppercase tracking-wider mb-1.5">Recommended Activities</h4>
                                <p class="text-slate-600 leading-relaxed">${recActivities}</p>
                            </div>
                            ` : ''}

                            ${avoidActivities ? `
                            <div class="bg-amber-50/40 p-4 rounded-xl border border-amber-100 text-xs">
                                <h4 class="font-black text-amber-800 uppercase tracking-wider mb-1.5">Activities to Avoid</h4>
                                <p class="text-slate-600 leading-relaxed">${avoidActivities}</p>
                            </div>
                            ` : ''}

                            ${lifestyle ? `
                            <div class="bg-purple-50/40 p-4 rounded-xl border border-purple-100 text-xs col-span-2">
                                <h4 class="font-black text-purple-800 uppercase tracking-wider mb-1.5">Lifestyle Recommendation</h4>
                                <p class="text-slate-600 leading-relaxed">${lifestyle}</p>
                            </div>
                            ` : ''}

                            ${addNotes ? `
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs col-span-2">
                                <h4 class="font-black text-slate-600 uppercase tracking-wider mb-1.5">Additional Notes</h4>
                                <p class="text-slate-600 leading-relaxed">${addNotes}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Footer / Signature -->
                    <div class="border-t pt-6 mt-8 border-slate-200 flex justify-between items-center text-[10px] text-slate-400">
                        <div>
                            <p>Digitally Signed and Verified</p>
                            <p class="font-bold text-slate-500">Orvella Healthcare Registry System</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-slate-500">${doctorName}</p>
                            <p>Attending Radiologist/Specialist</p>
                        </div>
                    </div>
                </div>
            `;

            const container = document.createElement('div');
            container.style.position = 'absolute';
            container.style.left = '-9999px';
            container.style.top = '0';
            container.style.width = '800px'; 
            container.innerHTML = `<div class="bg-white p-8">${reportHtml}</div>`;
            document.body.appendChild(container);

            const opt = {
                margin:       0.5,
                filename:     `Medical_Report_${patientName.replace(/\s+/g, '_')}_Scan_${scanIndex}.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            const generatePDF = () => {
                html2pdf().set(opt).from(container).save().then(() => {
                    document.body.removeChild(container);
                    if (typeof PageLoader !== 'undefined') PageLoader.finish();
                });
            };

            if (typeof html2pdf === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
                script.onload = generatePDF;
                document.head.appendChild(script);
            } else {
                generatePDF();
            }
        }
    };
}


</script>
@endsection
