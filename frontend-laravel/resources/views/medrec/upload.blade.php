@extends('layouts.dashboard')

@section('title', 'Diagnostic CT Scan & Vitals Upload')

@section('dashboard_content')
<div class="max-w-5xl mx-auto space-y-6" x-data="uploadForm()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-4">
        <div class="flex-1">
            <div class="flex items-center space-x-4 mb-3">
                <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-2xl shadow-md shadow-blue-500/20">
                    <i data-lucide="upload-cloud" class="w-7 h-7"></i>
                </div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">Upload CT Scan</h2>
            </div>
            <p class="text-slate-500 text-base max-w-2xl leading-relaxed ml-1">
                Submit medical imaging and baseline patient vitals for diagnostic processing and specialist review.
            </p>
        </div>
        <div class="flex items-center space-x-2 text-sm mt-2 md:mt-0">
            <span class="flex items-center text-blue-600 bg-blue-50 px-4 py-2 rounded-xl font-bold border border-blue-100 shadow-sm">
                <i data-lucide="shield-check" class="w-4 h-4 mr-1.5"></i> HIPAA Compliant
            </span>
        </div>
    </div>

    <!-- Toasts / Alerts -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl shadow-sm flex items-start space-x-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0"></i>
            <div class="flex-1 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 p-4 rounded-2xl shadow-sm flex items-start space-x-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0"></i>
            <div class="flex-1 text-sm font-medium text-red-800">{{ session('error') }}</div>
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden relative">
        <div class="absolute right-0 top-0 w-64 h-64 bg-gradient-to-bl from-blue-50 to-transparent rounded-bl-full pointer-events-none opacity-50 z-0"></div>
        
        <form action="{{ route('medrec.upload.post') }}" method="POST" enctype="multipart/form-data" @submit="validateForm($event)" class="p-6 md:p-10 relative z-10 space-y-8">
            @csrf
            @if(request('reupload'))
                <input type="hidden" name="reupload_scan_id" value="{{ request('reupload') }}">
            @endif
            
            <!-- Section 1: Patient & Specialist Assignment -->
            <div>
                <h3 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center space-x-2 border-b border-slate-100 pb-2">
                    <i data-lucide="user-check" class="w-4 h-4 text-blue-600"></i>
                    <span>1. Patient & Doctor Assignment</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="patient_id" class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Select Patient <span class="text-red-500">*</span></label>
                        <div class="relative group">
                            <i data-lucide="user" class="absolute left-4 top-3.5 w-4 h-4 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                            <select id="patient_id" name="patient_id" x-model="formData.patient_id" required class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-semibold text-slate-700 appearance-none shadow-sm cursor-pointer hover:bg-slate-100/50">
                                <option value="">-- Choose Patient --</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient['id'] }}" {{ old('patient_id') == $patient['id'] ? 'selected' : '' }}>
                                        {{ $patient['name'] ?? 'Unknown' }} (ID: {{ $patient['id'] }})
                                    </option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-3.5 w-4 h-4 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <div>
                        <label for="doctor_id" class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Assign Specialist <span class="text-red-500">*</span></label>
                        <div class="relative group">
                            <i data-lucide="stethoscope" class="absolute left-4 top-3.5 w-4 h-4 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                            <select id="doctor_id" name="doctor_id" x-model="formData.doctor_id" required class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-semibold text-slate-700 appearance-none shadow-sm cursor-pointer hover:bg-slate-100/50">
                                <option value="">-- Choose Doctor --</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor['id'] }}" {{ old('doctor_id') == $doctor['id'] ? 'selected' : '' }}>
                                        Dr. {{ $doctor['full_name'] ?? 'Unknown' }} ({{ $doctor['specialty'] ?? 'Specialist' }})
                                    </option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-3.5 w-4 h-4 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Medical Vitals -->
            <div>
                <h3 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center space-x-2 border-b border-slate-100 pb-2">
                    <i data-lucide="activity" class="w-4 h-4 text-emerald-600"></i>
                    <span>2. Baseline Patient Vitals</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                    <!-- Systolic -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Systolic (mmHg) <span class="text-red-500">*</span></label>
                        <input type="number" name="systolic" x-model="formData.systolic" required min="50" max="250" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-bold text-slate-800 placeholder:text-slate-400" placeholder="120">
                    </div>
                    <!-- Diastolic -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Diastolic (mmHg) <span class="text-red-500">*</span></label>
                        <input type="number" name="diastolic" x-model="formData.diastolic" required min="30" max="150" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-bold text-slate-800 placeholder:text-slate-400" placeholder="80">
                    </div>
                    <!-- Heart Rate -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Heart Rate (bpm) <span class="text-red-500">*</span></label>
                        <input type="number" name="heart_rate" x-model="formData.heart_rate" required min="30" max="200" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-bold text-slate-800 placeholder:text-slate-400" placeholder="72">
                    </div>
                    <!-- Weight -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Weight (kg) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.1" name="weight" x-model="formData.weight" required min="10" max="300" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-bold text-slate-800 placeholder:text-slate-400" placeholder="68.5">
                    </div>
                    <!-- SpO2 Oxygen Level -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">SpO2 / Oxygen (%) <span class="text-red-500">*</span></label>
                        <input type="number" name="oxygen_level" x-model="formData.oxygen_level" required min="70" max="100" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-bold text-slate-800 placeholder:text-slate-400" placeholder="98">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    <!-- Temperature -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Temperature (°C) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.1" name="temperature" x-model="formData.temperature" required min="34" max="42" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-bold text-slate-800 placeholder:text-slate-400" placeholder="36.5">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Initial Clinical Notes <span class="text-red-500">*</span></label>
                    <textarea name="notes" x-model="formData.notes" rows="2" required minlength="5" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-medium text-slate-800 placeholder:text-slate-400 resize-none" placeholder="Enter baseline symptoms, reasons for scan, or patient state..."></textarea>
                </div>
            </div>

            <!-- Section 3: CT Scan Imaging Upload -->
            <div>
                <h3 class="text-sm font-extrabold text-slate-800 mb-4 flex items-center space-x-2 border-b border-slate-100 pb-2">
                    <i data-lucide="image" class="w-4 h-4 text-indigo-600"></i>
                    <span>3. Medical Imaging File (CT Scan)</span>
                </h3>

                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center justify-between">
                        <span>Upload CT Scan Image <span class="text-red-500">*</span></span>
                        <span class="text-[10px] bg-slate-100 text-slate-600 px-2.5 py-0.5 rounded-lg font-bold">JPEG, PNG up to 5MB</span>
                    </label>
                    
                    <div class="relative w-full rounded-3xl border-2 border-dashed transition-all duration-300 overflow-hidden"
                         :class="dragOver ? 'border-blue-500 bg-blue-50/50' : (filePreview ? 'border-transparent bg-slate-950' : 'border-slate-300 hover:border-blue-400 hover:bg-slate-50')"
                         @dragover.prevent="dragOver = true"
                         @dragleave.prevent="dragOver = false"
                         @drop.prevent="dragOver = false; handleDrop($event)">
                        
                        <input id="scan_image" name="scan_image" type="file" required accept="image/jpeg,image/png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" @change="handleFileChange($event)" x-ref="fileInput">
                        
                        <!-- Upload State -->
                        <div class="py-16 px-6 text-center" x-show="!filePreview">
                            <div class="w-20 h-20 bg-white rounded-full shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-4 transition-transform duration-300" :class="dragOver ? 'scale-110 shadow-blue-500/20' : ''">
                                <i data-lucide="upload-cloud" class="w-10 h-10 text-blue-500"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-700 mb-1">Click or drag and drop CT Scan image here</p>
                            <p class="text-xs text-slate-500">High resolution axial CT slice required for diagnostic review by specialist.</p>
                        </div>

                        <!-- Preview State -->
                        <div class="relative w-full h-[360px] bg-slate-950 flex items-center justify-center p-4" x-show="filePreview" style="display: none;">
                            <img :src="filePreview" class="max-w-full max-h-full object-contain rounded-xl shadow-2xl" alt="CT Scan Preview">
                            
                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent pointer-events-none"></div>
                            
                            <!-- File Info -->
                            <div class="absolute bottom-4 left-6 right-6 flex items-center justify-between pointer-events-none z-10">
                                <div class="flex items-center space-x-3">
                                    <div class="bg-blue-600/90 backdrop-blur text-white px-3 py-1.5 rounded-xl text-[10px] font-mono font-bold tracking-widest border border-white/20 shadow-lg">READY FOR REVIEW</div>
                                    <span class="text-xs text-white font-bold truncate max-w-[240px]" x-text="fileName"></span>
                                </div>
                                <span class="text-xs font-mono font-bold text-slate-300" x-text="fileSize"></span>
                            </div>

                            <!-- Remove Button -->
                            <button type="button" @click.prevent="removeFile()" class="absolute top-4 right-4 z-30 p-3 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl shadow-lg hover:shadow-rose-500/30 transition-all group pointer-events-auto" title="Remove Image">
                                <i data-lucide="trash-2" class="w-5 h-5 group-hover:scale-110 transition-transform"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Progress Bar (Real %) -->
            <div x-show="isSubmitting" class="space-y-2 pt-4" style="display: none;">
                <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                    <span class="flex items-center">
                        <i data-lucide="loader-2" class="w-4 h-4 mr-2 text-blue-600 animate-spin"></i>
                        <span x-text="uploadProgress < 90 ? 'Uploading Scan...' : (uploadProgress < 100 ? 'Processing & Queuing...' : 'Done! Redirecting...')"></span>
                    </span>
                    <span x-text="uploadProgress + '%'"></span>
                </div>
                <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-300 rounded-full" :style="'width: ' + uploadProgress + '%'"></div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button type="submit" :disabled="isSubmitting" class="w-full flex items-center justify-center space-x-2 py-4 px-6 border border-transparent text-sm font-bold rounded-2xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 transition-all shadow-lg hover:shadow-blue-500/30 group transform hover:-translate-y-0.5">
                    <span x-text="isSubmitting ? 'Processing Upload...' : 'Submit for Specialist Review'"></span>
                    <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
                </button>
            </div>
        </form>
    </div>


</div>

<script>
function uploadForm() {
    return {
        fileName: '',
        fileSize: '',
        filePreview: null,
        dragOver: false,
        isSubmitting: false,
        uploadProgress: 0,
        formData: {
            patient_id: '{{ old("patient_id", data_get($reuploadScan ?? [], "patient_id", "")) }}',
            doctor_id: '{{ old("doctor_id", data_get($reuploadScan ?? [], "doctor_id", "")) }}',
            systolic: '{{ old("systolic", data_get($reuploadScan ?? [], "health_record.systolic", "")) }}',
            diastolic: '{{ old("diastolic", data_get($reuploadScan ?? [], "health_record.diastolic", "")) }}',
            heart_rate: '{{ old("heart_rate", data_get($reuploadScan ?? [], "health_record.heart_rate", "")) }}',
            weight: '{{ old("weight", data_get($reuploadScan ?? [], "health_record.weight", "")) }}',
            oxygen_level: '{{ old("oxygen_level", data_get($reuploadScan ?? [], "health_record.oxygen_level", "")) }}',
            temperature: '{{ old("temperature", data_get($reuploadScan ?? [], "health_record.temperature", "")) }}',
            notes: {!! json_encode(old('notes', data_get($reuploadScan ?? [], 'health_record.notes', ''))) !!}
        },

        handleFileChange(event) {
            const file = event.target.files[0];
            if (file) this.processFile(file);
        },

        handleDrop(event) {
            const file = event.dataTransfer.files[0];
            if (file && (file.type === 'image/jpeg' || file.type === 'image/png')) {
                this.$refs.fileInput.files = event.dataTransfer.files;
                this.processFile(file);
            } else {
                alert('Invalid file type. Only JPEG and PNG images are allowed.');
            }
        },

        processFile(file) {
            // Validate Size (5MB = 5 * 1024 * 1024)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB limit. Please upload a smaller image.');
                this.removeFile();
                return;
            }

            this.fileName = file.name;
            this.fileSize = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            const reader = new FileReader();
            reader.onload = (e) => {
                this.filePreview = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        removeFile() {
            this.fileName = '';
            this.fileSize = '';
            this.filePreview = null;
            this.$refs.fileInput.value = '';
        },

        validateForm(event) {
            if (!this.filePreview) {
                event.preventDefault();
                alert('Please upload a CT Scan image.');
                return false;
            }

            // Prevent default form submission - we use XHR for real progress
            event.preventDefault();

            this.isSubmitting = true;
            this.uploadProgress = 0;

            const form = event.target;
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            // Track real upload progress
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    // Map upload phase to 0-90%
                    this.uploadProgress = Math.round((e.loaded / e.total) * 90);
                }
            });

            xhr.upload.addEventListener('load', () => {
                // Upload done, now server is processing
                this.uploadProgress = 90;
            });

            xhr.addEventListener('load', () => {
                this.uploadProgress = 100;
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success && data.redirect) {
                        setTimeout(() => { window.location.href = data.redirect; }, 400);
                    } else if (!data.success) {
                        this.isSubmitting = false;
                        this.uploadProgress = 0;
                        alert(data.message || 'Upload failed. Please try again.');
                    } else {
                        // Fallback: follow XHR responseURL
                        setTimeout(() => { window.location.href = xhr.responseURL || form.action; }, 400);
                    }
                } catch(e) {
                    // Non-JSON response (e.g. validation error page redirect)
                    setTimeout(() => { window.location.href = xhr.responseURL || form.action; }, 400);
                }
            });

            xhr.addEventListener('error', () => {
                this.isSubmitting = false;
                this.uploadProgress = 0;
                alert('Upload failed due to a network error. Please try again.');
            });

            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        }
    }
}
</script>
@endsection
