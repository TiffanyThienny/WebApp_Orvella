@extends('layouts.dashboard')

@section('title', 'Clinical Diagnostic Workstation')

@section('dashboard_content')
<div class="max-w-7xl mx-auto space-y-6" x-data="diagnosticWorkstation()">
    <!-- Back Button -->
    <div class="flex items-center">
        <a href="{{ route('doctor.scans.queue') }}" class="inline-flex items-center text-sm font-bold text-slate-500 hover:text-blue-600 transition-colors bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100 hover:border-blue-100 hover:shadow-md">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
            Back to Queue
        </a>
    </div>

    <!-- Patient Context Bar -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-blue-500/25 border border-blue-400/20 flex-shrink-0">
                {{ substr($scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'U', 0, 1) }}
            </div>
            <div>
                <div class="flex items-center space-x-3">
                    <h3 class="text-lg font-black text-slate-800 leading-tight">{{ $scan['patient']['user']['full_name'] ?? $scan['patient']['name'] ?? 'Unknown Patient' }}</h3>
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-md text-[10px] font-bold">ID: {{ str_pad($scan['id'] ?? 0, 5, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="flex flex-wrap items-center text-xs text-slate-500 mt-2 gap-4">
                    <span class="flex items-center"><i data-lucide="calendar" class="w-3.5 h-3.5 mr-1 text-slate-400"></i> Uploaded: {{ \Carbon\Carbon::parse($scan['created_at'] ?? 'now')->format('d M Y, H:i') }}</span>
                    <span class="flex items-center"><i data-lucide="user" class="w-3.5 h-3.5 mr-1 text-slate-400"></i> DOB: {{ $scan['patient']['dob'] ?? 'N/A' }} ({{ $scan['patient']['gender'] ?? 'N/A' }})</span>
                    @if(!empty($scan['patient']['allergies']))
                        <span class="flex items-center text-rose-600 font-semibold"><i data-lucide="alert-circle" class="w-3.5 h-3.5 mr-1"></i> Allergies: {{ $scan['patient']['allergies'] }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            @if(strtolower($scan['status'] ?? '') == 'analyzed')
                <div class="flex flex-col items-end">
                    <span class="px-4 py-2 bg-amber-500 text-white border border-amber-400 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-amber-500/20 flex items-center animate-pulse">
                        <i data-lucide="alert-triangle" class="w-4 h-4 mr-1.5"></i> Pending Doctor Review
                    </span>
                    <span class="text-[10px] text-slate-400 font-bold mt-1">Requires Clinical Validation</span>
                </div>
            @else
                <span class="px-4 py-2 bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold uppercase tracking-widest">
                    Status: {{ $scan['status'] ?? 'pending' }}
                </span>
            @endif
        </div>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 p-4 rounded-2xl shadow-sm flex items-start space-x-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 mt-0.5"></i>
            <div class="flex-1 text-sm font-medium text-red-800">{{ session('error') }}</div>
        </div>
    @endif

    <!-- Main Content Workstation Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- LEFT COLUMN (lg:col-span-7): Assessment & Visual Explanation -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- 1. Diagnostic Assessment (Prediction Summary) -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex items-center space-x-2.5">
                    <div class="p-2 bg-blue-50 text-blue-600 rounded-xl"><i data-lucide="activity" class="w-5 h-5"></i></div>
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-sm">Diagnostic Assessment (Prediction Summary)</h4>
                        <p class="text-[11px] text-slate-500 font-medium">AI neural assessment prediction output</p>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    @if(!empty($scan['ai_result']))
                        @php
                            $risk = strtolower($scan['ai_result']['risk_level'] ?? $scan['ai_result']['prediction_label'] ?? '');
                            $badgeColor = 'bg-slate-100 text-slate-700 border-slate-200';
                            if (str_contains($risk, 'tumor') || str_contains($risk, 'high') || str_contains($risk, 'critical')) {
                                $badgeColor = 'bg-rose-50 text-rose-700 border-rose-200';
                            } elseif (str_contains($risk, 'medium') || str_contains($risk, 'abnormal')) {
                                $badgeColor = 'bg-amber-50 text-amber-700 border-amber-200';
                            } else {
                                $badgeColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            }
                        @endphp
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest flex items-center"><i data-lucide="sparkles" class="w-3 h-3 mr-1"></i> AI Neural Assessment</span>
                            <span class="px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded border {{ $badgeColor }}">
                                {{ $scan['ai_result']['risk_level'] ?? 'Diagnostic Clear' }}
                            </span>
                        </div>
                        <h4 class="text-base font-black text-slate-900 uppercase tracking-tight">{{ $scan['ai_result']['prediction_label'] ?? 'Normal / No Abnormality Found' }}</h4>
                        <p class="text-xs text-slate-600 leading-relaxed font-medium">"{{ $scan['ai_result']['result_text'] ?? 'No significant radiological abnormalities detected.' }}"</p>
                        
                        <!-- Confidence Meter -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider">Confidence Score</span>
                                <span class="text-base font-black text-blue-700">{{ number_format(($scan['ai_result']['confidence'] ?? 0) * 100, 1) }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden shadow-inner">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-700" style="width: {{ number_format(($scan['ai_result']['confidence'] ?? 0) * 100, 1) }}%"></div>
                            </div>
                        </div>
                    @elseif(($scan['status'] ?? '') == 'ai_processing')
                        <div class="flex flex-col items-center justify-center py-6 space-y-3 text-blue-500">
                            <div class="w-10 h-10 border-4 border-blue-500/30 border-t-blue-500 rounded-full animate-spin"></div>
                            <div class="text-xs font-bold uppercase tracking-widest animate-pulse">Processing Analysis...</div>
                        </div>
                    @else
                        <div class="space-y-3">
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start space-x-3">
                                <div class="p-2 bg-amber-100 text-amber-600 rounded-xl flex-shrink-0">
                                    <i data-lucide="zap-off" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h5 class="font-bold text-slate-800 text-xs mb-1">Analysis Pending</h5>
                                    <p class="text-xs text-slate-500 font-medium leading-relaxed">No automated result available. Manual review required or run analysis below.</p>
                                </div>
                            </div>
                            <form action="{{ route('doctor.scans.analyze', $scan['id']) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-blue-500/25 flex items-center justify-center space-x-1.5">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    <span>Run Diagnostic Analysis</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 2. AI Visual Explanation Section (Placed below prediction summary) -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-6">
                <!-- Section Header -->
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-center space-x-3">
                        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-2xl border border-blue-100 shadow-sm">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-slate-800 text-sm">AI Visual Explanation (XAI)</h4>
                            <p class="text-[11px] text-slate-500">ViT-L/14 Patch Attention Map visualizing neural processing regions</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-bold uppercase tracking-wider flex items-center border border-slate-200">
                            <i data-lucide="cpu" class="w-3.5 h-3.5 mr-1.5 text-blue-500"></i> ViT-L/14 Attention
                        </span>
                    </div>
                </div>

                <!-- 50/50 Dual Column Layout -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Card: Original Retinal Image -->
                    <div class="bg-slate-50 rounded-2xl border border-slate-200 overflow-hidden flex flex-col">
                        <div class="p-3 border-b border-slate-150 flex items-center justify-between bg-white/60">
                            <span class="px-2.5 py-0.5 bg-slate-200 text-slate-700 rounded-lg text-[10px] font-black uppercase tracking-wider">
                                Original Image
                            </span>
                            
                            <!-- Zoom & Fullscreen Controls -->
                            <div class="flex items-center space-x-1">
                                <button @click="zoomOriginal = Math.max(0.5, zoomOriginal - 0.25)" class="p-1 hover:bg-slate-200 rounded text-slate-500" title="Zoom Out">
                                    <i data-lucide="zoom-out" class="w-3.5 h-3.5"></i>
                                </button>
                                <span class="text-[10px] font-mono font-bold text-slate-600 px-1" x-text="Math.round(zoomOriginal * 100) + '%'"></span>
                                <button @click="zoomOriginal = Math.min(3, zoomOriginal + 0.25)" class="p-1 hover:bg-slate-200 rounded text-slate-500" title="Zoom In">
                                    <i data-lucide="zoom-in" class="w-3.5 h-3.5"></i>
                                </button>
                                <button @click="zoomOriginal = 1" class="p-1 hover:bg-slate-200 rounded text-slate-500 text-[9px] font-bold uppercase" title="Reset Zoom">
                                    Reset
                                </button>
                                <div class="w-px h-3 bg-slate-250 mx-1"></div>
                                <button @click="originalFullscreen = true" class="p-1 hover:bg-slate-200 rounded text-slate-600" title="Fullscreen Preview">
                                    <i data-lucide="maximize" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="relative bg-slate-950 aspect-[4/3] flex items-center justify-center overflow-hidden">
                            <div class="relative w-full h-full flex items-center justify-center transition-transform duration-350 ease-out"
                                 :style="'transform: scale(' + zoomOriginal + ')'">
                                <img src="{{ Str::startsWith($scan['image_url'] ?? '', 'http') ? $scan['image_url'] : config('services.go_api.url', 'http://localhost:8080') . '/' . $scan['image_url'] }}" 
                                     alt="Original Retinal Image" 
                                     class="max-w-full max-h-full object-contain">
                            </div>
                        </div>
                    </div>

                    <!-- Right Card: AI ViT Attention Heatmap -->
                    <div class="bg-slate-50 rounded-2xl border border-slate-200 overflow-hidden flex flex-col">
                        <div class="p-3 border-b border-slate-150 flex items-center justify-between bg-white/60">
                            <span class="px-2.5 py-0.5 bg-blue-100 text-blue-700 rounded-lg text-[10px] font-black uppercase tracking-wider ">
                                AI ViT Attention
                            </span>
                            
                            <!-- Zoom & Fullscreen Controls -->
                            <div class="flex items-center space-x-1">
                                <button @click="zoomHeatmap = Math.max(0.5, zoomHeatmap - 0.25)" class="p-1 hover:bg-slate-200 rounded text-slate-500" title="Zoom Out">
                                    <i data-lucide="zoom-out" class="w-3.5 h-3.5"></i>
                                </button>
                                <span class="text-[10px] font-mono font-bold text-slate-600 px-1" x-text="Math.round(zoomHeatmap * 100) + '%'"></span>
                                <button @click="zoomHeatmap = Math.min(3, zoomHeatmap + 0.25)" class="p-1 hover:bg-slate-200 rounded text-slate-500" title="Zoom In">
                                    <i data-lucide="zoom-in" class="w-3.5 h-3.5"></i>
                                </button>
                                <button @click="zoomHeatmap = 1" class="p-1 hover:bg-slate-200 rounded text-slate-500 text-[9px] font-bold uppercase" title="Reset Zoom">
                                    Reset
                                </button>
                                <div class="w-px h-3 bg-slate-250 mx-1"></div>
                                <button @click="heatmapFullscreen = true" class="p-1 hover:bg-slate-200 rounded text-slate-600" title="Fullscreen Preview">
                                    <i data-lucide="maximize" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="relative bg-slate-950 aspect-[4/3] flex items-center justify-center overflow-hidden">
                            <!-- Skeleton Loader -->
                            <div x-show="heatmapLoading || '{{ $scan['status'] }}' === 'ai_processing'" 
                                 class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center space-y-3 z-10">
                                <div class="w-12 h-12 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-blue-500 shadow-xl">
                                    <i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>
                                </div>
                                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-400 animate-pulse">Loading Heatmap...</span>
                            </div>

                            @if(!empty($scan['ai_result']))
                                <div class="relative w-full h-full flex items-center justify-center transition-transform duration-350 ease-out"
                                     :style="'transform: scale(' + zoomHeatmap + ')'"
                                     x-show="!heatmapLoading && '{{ $scan['status'] }}' !== 'ai_processing'">
                                    <img src="{{ Str::startsWith($scan['ai_result']['analyzed_image_url'] ?? $scan['image_url'] ?? '', 'http') ? ($scan['ai_result']['analyzed_image_url'] ?? $scan['image_url']) : config('services.go_api.url', 'http://localhost:8080') . '/' . ($scan['ai_result']['analyzed_image_url'] ?? $scan['image_url']) }}" 
                                         alt="AI ViT-L/14 Attention Heatmap" 
                                         class="max-w-full max-h-full object-contain"
                                         x-init="if ($el.complete) heatmapLoading = false"
                                         @load="heatmapLoading = false">
                                </div>
                            @else
                                @if(($scan['status'] ?? '') != 'ai_processing')
                                    <div class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center text-center p-6 space-y-3">
                                        <div class="p-3 bg-amber-500/10 text-amber-500 rounded-2xl border border-amber-500/20">
                                            <i data-lucide="zap-off" class="w-6 h-6"></i>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold uppercase tracking-widest text-slate-350">No Heatmap Generated</div>
                                            <p class="text-[9px] text-slate-500 mt-1 max-w-[180px]">Run diagnosis to calculate activation mappings</p>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Info / Disclaimer Banner (with helper text below heatmap) -->
                <div class="bg-blue-50/50 border border-blue-100/60 p-4 rounded-2xl flex items-start space-x-3">
                    <i data-lucide="info" class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0"></i>
                    <div>
                        <p class="text-xs text-blue-900 font-bold">
                            Highlighted regions show where the Vision Transformer (ViT-L/14) focuses its self-attention.
                        </p>
                        <p class="text-[10px] text-slate-500 mt-1 leading-relaxed">
                            Following the ViT-L/14 pipeline, the image is divided into a 16x16 grid of patches (tokens). The model processes these through self-attention layers to determine relationships. Anomalous or highly influential patches are highlighted in a red gradient, with higher opacity indicating higher contribution to the prediction.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN (lg:col-span-5): Clinical Action & Validation -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50 sticky top-0 z-10 flex items-center space-x-2.5">
                    <div class="p-2 bg-indigo-100 text-indigo-600 rounded-xl"><i data-lucide="file-check-2" class="w-5 h-5"></i></div>
                    <div>
                        <h4 class="font-extrabold text-slate-800 text-sm">Clinical Action & Validation</h4>
                        <p class="text-[11px] text-slate-500 font-medium">Complete mandatory medical prescription</p>
                    </div>
                </div>
                
                <div class="p-6 flex-1 flex flex-col">
                    <!-- Check if already completed -->
                    @if(in_array(strtolower($scan['status'] ?? ''), ['approved', 'rejected']))
                        @php
                            $rawNotes = isset($scan['diagnosis']) && is_array($scan['diagnosis']) ? ($scan['diagnosis']['notes'] ?? '') : '';
                            $parsedNotes = json_decode($rawNotes, true);
                            $medNotes = is_array($parsedNotes) ? ($parsedNotes['medical_notes'] ?? $rawNotes) : $rawNotes;
                            $diagFoodAllowed = is_array($parsedNotes) ? ($parsedNotes['food_allowed'] ?? '') : '';
                            $diagFoodAvoided = is_array($parsedNotes) ? ($parsedNotes['food_avoided'] ?? '') : '';
                            $diagNextCheckup = is_array($parsedNotes) ? ($parsedNotes['next_checkup'] ?? '') : '';
                        @endphp
                        <div class="space-y-4 mb-4">
                            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl">
                                <h5 class="text-sm font-black text-slate-800 mb-2 flex items-center"><i data-lucide="check-circle" class="w-4 h-4 mr-1.5 text-emerald-600"></i> Clinical Validation Completed</h5>
                                <p class="text-xs text-slate-500 mb-4">This scan has been reviewed and approved. Summary below:</p>
                                
                                <div class="space-y-3">
                                    <div class="bg-white border border-slate-100 p-3 rounded-xl shadow-sm">
                                        <h6 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Medical Diagnosis</h6>
                                        <p class="text-sm font-medium text-slate-700 leading-relaxed">{{ $medNotes ?: 'No notes available.' }}</p>
                                    </div>
                                    @if($diagFoodAllowed)
                                    <div class="bg-emerald-50 border border-emerald-100 p-3 rounded-xl">
                                        <h6 class="text-[10px] font-black text-emerald-700 uppercase tracking-widest mb-1">Foods Allowed</h6>
                                        <p class="text-sm text-slate-600">{{ $diagFoodAllowed }}</p>
                                    </div>
                                    @endif
                                    @if($diagFoodAvoided)
                                    <div class="bg-rose-50 border border-rose-100 p-3 rounded-xl">
                                        <h6 class="text-[10px] font-black text-rose-700 uppercase tracking-widest mb-1">Foods to Avoid</h6>
                                        <p class="text-sm text-slate-600">{{ $diagFoodAvoided }}</p>
                                    </div>
                                    @endif
                                    @if($diagNextCheckup)
                                    <div class="bg-blue-50 border border-blue-100 p-3 rounded-xl flex items-center space-x-2">
                                        <i data-lucide="calendar-check" class="w-4 h-4 text-blue-600"></i>
                                        <p class="text-sm font-bold text-slate-800">Next checkup: {{ $diagNextCheckup }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else

                    <!-- Approve Form -->
                    <form x-show="action == 'approve'" action="{{ route('doctor.scans.approve', $scan['id']) }}" method="POST" @submit="validateApproveForm($event)" class="space-y-5 flex-1 flex flex-col">
                        @csrf
                        @method('PUT')

                        <div class="bg-blue-50/50 border border-blue-105 p-4 rounded-2xl flex items-start space-x-3 mb-2">
                            <i data-lucide="info" class="w-5 h-5 text-blue-650 mt-0.5 flex-shrink-0"></i>
                            <div class="text-xs text-blue-905 leading-relaxed font-medium">
                                All fields below are <span class="font-bold underline">mandatory</span>.
                            </div>
                        </div>

                        <!-- 1. Medical Notes -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center"><i data-lucide="stethoscope" class="w-3.5 h-3.5 mr-1.5 text-blue-600"></i> Medical Notes <span class="text-red-500 ml-0.5">*</span></span>
                            </label>
                            <textarea name="notes" x-model="formData.notes" rows="2" required minlength="5" @input="autoResize($event)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 outline-none transition-all text-xs placeholder:text-slate-450 resize-none font-medium text-slate-700" placeholder="Clinical findings and diagnosis..."></textarea>
                        </div>

                        <!-- 2. Food Allowed -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center"><i data-lucide="utensils" class="w-3.5 h-3.5 mr-1.5 text-emerald-600"></i> Recommended Foods <span class="text-red-500 ml-0.5">*</span></span>
                            </label>
                            <textarea name="food_allowed" x-model="formData.food_allowed" rows="2" required minlength="5" @input="autoResize($event)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 outline-none transition-all text-xs placeholder:text-slate-450 resize-none font-medium text-slate-700" placeholder="Nutritious foods..."></textarea>
                        </div>

                        <!-- 3. Food Avoided -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center"><i data-lucide="ban" class="w-3.5 h-3.5 mr-1.5 text-rose-600"></i> Foods to Avoid <span class="text-red-500 ml-0.5">*</span></span>
                            </label>
                            <textarea name="food_avoided" x-model="formData.food_avoided" rows="2" required minlength="5" @input="autoResize($event)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 outline-none transition-all text-xs placeholder:text-slate-450 resize-none font-medium text-slate-700" placeholder="Foods to avoid..."></textarea>
                        </div>

                        <!-- 4. Recommended Activities -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center"><i data-lucide="activity" class="w-3.5 h-3.5 mr-1.5 text-emerald-600"></i> Recommended Activities <span class="text-red-500 ml-0.5">*</span></span>
                            </label>
                            <textarea name="recommended_activities" x-model="formData.recommended_activities" rows="2" required minlength="5" @input="autoResize($event)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 outline-none transition-all text-xs placeholder:text-slate-450 resize-none font-medium text-slate-700" placeholder="Recommended activities..."></textarea>
                        </div>

                        <!-- 5. Avoided Activities -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center"><i data-lucide="alert-octagon" class="w-3.5 h-3.5 mr-1.5 text-rose-600"></i> Activities to Avoid <span class="text-red-500 ml-0.5">*</span></span>
                            </label>
                            <textarea name="avoided_activities" x-model="formData.avoided_activities" rows="2" required minlength="5" @input="autoResize($event)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 outline-none transition-all text-xs placeholder:text-slate-450 resize-none font-medium text-slate-700" placeholder="Activities to avoid..."></textarea>
                        </div>

                        <!-- 6. Lifestyle Recommendation -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center"><i data-lucide="heart-handshake" class="w-3.5 h-3.5 mr-1.5 text-blue-600"></i> Lifestyle Recommendation <span class="text-red-500 ml-0.5">*</span></span>
                            </label>
                            <textarea name="lifestyle_recommendations" x-model="formData.lifestyle_recommendations" rows="2" required minlength="5" @input="autoResize($event)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 outline-none transition-all text-xs placeholder:text-slate-450 resize-none font-medium text-slate-700" placeholder="Lifestyle recommendations..."></textarea>
                        </div>

                        <!-- 7. Next Checkup -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center"><i data-lucide="calendar-clock" class="w-3.5 h-3.5 mr-1.5 text-indigo-600"></i> Follow-up Checkup Schedule <span class="text-red-500 ml-0.5">*</span></span>
                            </label>
                            <input type="text" name="next_checkup" x-model="formData.next_checkup" required minlength="3" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 outline-none transition-all text-xs placeholder:text-slate-450 font-medium text-slate-700" placeholder="e.g., In 1 week, 1 month...">
                        </div>

                        <!-- 8. Additional Notes -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center"><i data-lucide="file-text" class="w-3.5 h-3.5 mr-1.5 text-slate-600"></i> Additional Notes <span class="text-red-500 ml-0.5">*</span></span>
                            </label>
                            <textarea name="additional_notes" x-model="formData.additional_notes" rows="2" required minlength="5" @input="autoResize($event)" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 outline-none transition-all text-xs placeholder:text-slate-450 resize-none font-medium text-slate-700" placeholder="Additional instructions..."></textarea>
                        </div>
                        
                        <!-- Action Toggle (Moved to bottom) -->
                        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 my-2 relative">
                            <div class="absolute inset-y-1 bg-white rounded-lg shadow-sm border border-slate-200 transition-all duration-300 ease-in-out" 
                                 :class="action == 'approve' ? 'left-1 w-[calc(50%-4px)]' : 'left-[50%] w-[calc(50%-4px)]'"></div>
                            
                            <button type="button" @click="action = 'approve'" :class="action == 'approve' ? 'text-emerald-700 font-extrabold text-[10px]' : 'text-slate-500 hover:text-slate-700 text-[10px]'" class="flex-1 py-2 rounded-lg z-10 flex items-center justify-center transition-colors">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5 mr-1" :class="action == 'approve' ? 'text-emerald-500' : ''"></i> Approve & Prescribe
                            </button>
                            <button type="button" @click="action = 'reject'" :class="action == 'reject' ? 'text-rose-700 font-extrabold text-[10px]' : 'text-slate-500 hover:text-slate-700 text-[10px]'" class="flex-1 py-2 rounded-lg z-10 flex items-center justify-center transition-colors">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 mr-1" :class="action == 'reject' ? 'text-rose-500' : ''"></i> Reject Scan
                            </button>
                        </div>

                        <div class="pt-2">
                            <button type="submit" :disabled="isSubmitting" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/30 transition-all flex items-center justify-center space-x-2 group text-xs">
                                <span x-text="isSubmitting ? 'Finalizing Prescription...' : 'Finalize & Approve Report'"></span>
                                <i data-lucide="file-check" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Reject Form -->
                    <form x-show="action == 'reject'" action="{{ route('doctor.scans.reject', $scan['id']) }}" method="POST" @submit="isSubmitting = true" class="space-y-5 flex-1 flex flex-col" style="display: none;">
                        @csrf
                        @method('PUT')
                        
                        <div class="bg-rose-50 border border-rose-200 p-4 rounded-xl flex items-start space-x-3 mb-2">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0"></i>
                            <div>
                                <h5 class="text-xs font-bold text-rose-800">Rejection Notice</h5>
                                <p class="text-[10px] text-rose-700 mt-1 font-medium leading-relaxed">Rejecting this scan will return it to the Medrec queue.</p>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between text-red-655">
                                <span class="flex items-center"><i data-lucide="message-square-x" class="w-3.5 h-3.5 mr-1.5"></i> Rejection Reason <span class="text-red-500 ml-0.5">*</span></span>
                            </label>
                            <textarea name="rejection_reason" rows="6" required minlength="5" class="w-full px-3 py-2 bg-white border border-red-200 rounded-xl focus:ring-2 focus:ring-red-600/20 focus:border-red-600 outline-none transition-all text-xs placeholder:text-red-300 resize-none font-medium text-slate-800" placeholder="Explain why this scan is invalid..."></textarea>
                        </div>
                        
                        <!-- Action Toggle (Moved to bottom) -->
                        <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 my-2 relative">
                            <div class="absolute inset-y-1 bg-white rounded-lg shadow-sm border border-slate-200 transition-all duration-300 ease-in-out" 
                                 :class="action == 'approve' ? 'left-1 w-[calc(50%-4px)]' : 'left-[50%] w-[calc(50%-4px)]'"></div>
                            
                            <button type="button" @click="action = 'approve'" :class="action == 'approve' ? 'text-emerald-700 font-extrabold text-[10px]' : 'text-slate-500 hover:text-slate-700 text-[10px]'" class="flex-1 py-2 rounded-lg z-10 flex items-center justify-center transition-colors">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5 mr-1" :class="action == 'approve' ? 'text-emerald-500' : ''"></i> Approve & Prescribe
                            </button>
                            <button type="button" @click="action = 'reject'" :class="action == 'reject' ? 'text-rose-700 font-extrabold text-[10px]' : 'text-slate-500 hover:text-slate-700 text-[10px]'" class="flex-1 py-2 rounded-lg z-10 flex items-center justify-center transition-colors">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5 mr-1" :class="action == 'reject' ? 'text-rose-500' : ''"></i> Reject Scan
                            </button>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-black uppercase tracking-widest shadow-lg shadow-rose-500/20 transition-all text-[10px] flex items-center justify-center gap-2" @click="if(!confirm('Reject this scan? The patient will be notified.')) event.preventDefault()">
                                <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Reject Scan & Notify
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <!-- Fullscreen Lightbox Modal for Original Image -->
    <div x-show="originalFullscreen" 
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 bg-slate-950/95 z-50 flex flex-col items-center justify-center p-4" 
         style="display: none;"
         @keydown.escape.window="originalFullscreen = false">
        
        <!-- Toolbar header -->
        <div class="absolute top-6 left-6 flex items-center space-x-3 text-white">
            <span class="px-3 py-1 bg-white/10 rounded-lg text-xs font-bold uppercase tracking-wider">Original Retinal Scan</span>
        </div>

        <!-- Close button -->
        <button @click="originalFullscreen = false" class="absolute top-6 right-6 p-3 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
        
        <!-- Image container -->
        <div class="relative w-full h-[70vh] flex items-center justify-center overflow-hidden">
            <img src="{{ Str::startsWith($scan['image_url'] ?? '', 'http') ? $scan['image_url'] : config('services.go_api.url', 'http://localhost:8080') . '/' . $scan['image_url'] }}" 
                 class="max-w-full max-h-full object-contain transition-transform duration-300"
                 :style="'transform: scale(' + zoomOriginal + ')'">
        </div>
        
        <!-- Control bar -->
        <div class="absolute bottom-10 bg-slate-900/90 backdrop-blur-xl p-2 rounded-2xl border border-white/15 shadow-2xl flex items-center space-x-2 text-white">
            <button @click="zoomOriginal = Math.max(0.5, zoomOriginal - 0.25)" class="p-2 hover:bg-white/10 rounded-xl transition-colors"><i data-lucide="zoom-out" class="w-4 h-4"></i></button>
            <span class="text-xs font-bold px-2 font-mono w-16 text-center" x-text="Math.round(zoomOriginal * 100) + '%'"></span>
            <button @click="zoomOriginal = Math.min(3, zoomOriginal + 0.25)" class="p-2 hover:bg-white/10 rounded-xl transition-colors"><i data-lucide="zoom-in" class="w-4 h-4"></i></button>
            <button @click="zoomOriginal = 1" class="px-3 py-1 hover:bg-white/10 rounded-xl transition-colors text-[10px] font-bold uppercase tracking-wider border border-white/10">Reset</button>
        </div>
    </div>

    <!-- Fullscreen Lightbox Modal for Heatmap -->
    <div x-show="heatmapFullscreen" 
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 bg-slate-950/95 z-50 flex flex-col items-center justify-center p-4" 
         style="display: none;"
         @keydown.escape.window="heatmapFullscreen = false">
        
        <!-- Toolbar header -->
        <div class="absolute top-6 left-6 flex items-center space-x-3 text-white">
            <span class="px-3 py-1 bg-blue-600 rounded-lg text-xs font-bold uppercase tracking-wider">AI ViT-L/14 Attention Heatmap</span>
        </div>

        <!-- Close button -->
        <button @click="heatmapFullscreen = false" class="absolute top-6 right-6 p-3 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
        
        <!-- Image container -->
        <div class="relative w-full h-[70vh] flex items-center justify-center overflow-hidden">
            @if(!empty($scan['ai_result']))
                <img src="{{ Str::startsWith($scan['ai_result']['analyzed_image_url'] ?? $scan['image_url'] ?? '', 'http') ? ($scan['ai_result']['analyzed_image_url'] ?? $scan['image_url']) : config('services.go_api.url', 'http://localhost:8080') . '/' . ($scan['ai_result']['analyzed_image_url'] ?? $scan['image_url']) }}" 
                     class="max-w-full max-h-full object-contain transition-transform duration-300"
                     :style="'transform: scale(' + zoomHeatmap + ')'">
            @endif
        </div>
        
        <!-- Control bar -->
        <div class="absolute bottom-10 bg-slate-900/90 backdrop-blur-xl p-2 rounded-2xl border border-white/15 shadow-2xl flex items-center space-x-2 text-white">
            <button @click="zoomHeatmap = Math.max(0.5, zoomHeatmap - 0.25)" class="p-2 hover:bg-white/10 rounded-xl transition-colors"><i data-lucide="zoom-out" class="w-4 h-4"></i></button>
            <span class="text-xs font-bold px-2 font-mono w-16 text-center" x-text="Math.round(zoomHeatmap * 100) + '%'"></span>
            <button @click="zoomHeatmap = Math.min(3, zoomHeatmap + 0.25)" class="p-2 hover:bg-white/10 rounded-xl transition-colors"><i data-lucide="zoom-in" class="w-4 h-4"></i></button>
            <button @click="zoomHeatmap = 1" class="px-3 py-1 hover:bg-white/10 rounded-xl transition-colors text-[10px] font-bold uppercase tracking-wider border border-white/10">Reset</button>
        </div>
    </div>
</div>

<script>
function diagnosticWorkstation() {
    return {
        action: 'approve',
        zoomOriginal: 1,
        zoomHeatmap: 1,
        overlayOpacity: 0.6,
        originalFullscreen: false,
        heatmapFullscreen: false,
        heatmapLoading: true,
        isSubmitting: false,
        formData: {
            notes: '',
            food_allowed: '',
            food_avoided: '',
            recommended_activities: '',
            avoided_activities: '',
            lifestyle_recommendations: '',
            next_checkup: '',
            additional_notes: ''
        },

        autoResize(event) {
            const elem = event.target;
            elem.style.height = 'auto';
            elem.style.height = (elem.scrollHeight) + 'px';
        },

        validateApproveForm(event) {
            for (let key in this.formData) {
                if (this.formData[key].trim().length < (key === 'next_checkup' ? 3 : 5)) {
                    event.preventDefault();
                    alert(`Please provide a more detailed response for "${key.replace(/_/g, ' ')}".`);
                    this.isSubmitting = false;
                    return false;
                }
            }
            this.isSubmitting = true;
        }
    }
}
</script>

@if(($scan['status'] ?? '') == 'ai_processing')
<script>
    setTimeout(function() {
        window.location.reload();
    }, 3000);
</script>
@endif
@endsection
