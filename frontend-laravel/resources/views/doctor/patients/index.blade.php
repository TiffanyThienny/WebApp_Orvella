@extends('layouts.dashboard')

@section('title', 'Patient Directory')

@section('dashboard_content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-2">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">My Patients</h2>
            <p class="text-slate-500 text-sm mt-1">Directory of all patients registered in the clinical system.</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="relative group">
                <i data-lucide="search" class="absolute left-3.5 top-2.5 w-4 h-4 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                <input type="text" placeholder="Search patient name or ID..." class="pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-sm shadow-sm w-64 md:w-80">
            </div>
            <button class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-blue-600 hover:bg-slate-50 hover:border-blue-200 shadow-sm transition-all">
                <i data-lucide="filter" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- Patients Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($patients as $patient)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-blue-200 transition-all overflow-hidden flex flex-col group relative">
            
            <!-- Card Header Pattern -->
            <div class="h-20 bg-gradient-to-br from-slate-100 to-slate-50 relative overflow-hidden">
                <div class="absolute inset-0 opacity-30 group-hover:opacity-60 transition-opacity bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
                <!-- Status Badge -->
                @php
                    $status = strtolower($patient['status'] ?? 'normal');
                @endphp
                <div class="absolute top-3 right-3">
                    @if($status == 'critical' || $status == 'abnormal')
                        <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold uppercase rounded-md tracking-widest shadow-sm flex items-center"><i data-lucide="activity" class="w-3 h-3 mr-1"></i> Monitor</span>
                    @else
                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase rounded-md tracking-widest shadow-sm flex items-center"><i data-lucide="check" class="w-3 h-3 mr-1"></i> Stable</span>
                    @endif
                </div>
            </div>

            <!-- Avatar Profile -->
            <div class="px-5 relative">
                <div class="w-16 h-16 rounded-2xl bg-white border border-slate-100 shadow-md flex items-center justify-center font-bold text-xl text-slate-400 absolute -top-8 left-5 group-hover:scale-105 transition-transform relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-cyan-50"></div>
                    <span class="relative z-10 text-blue-600">{{ strtoupper(substr($patient['name'] ?? 'P', 0, 1)) }}</span>
                </div>
                
                <div class="mt-10 mb-4">
                    <h4 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition-colors truncate">{{ $patient['name'] ?? 'Unknown' }}</h4>
                    <p class="text-xs text-slate-500 font-mono mt-0.5">ID: {{ str_pad($patient['id'], 6, '0', STR_PAD_LEFT) }}</p>
                </div>
            </div>

            <!-- Patient Info -->
            <div class="px-5 py-4 border-t border-slate-50 flex-1 bg-slate-50/50 space-y-3">
                <div class="flex items-center text-sm">
                    <div class="w-6 h-6 rounded-lg bg-white flex items-center justify-center mr-3 shadow-sm border border-slate-100 text-slate-400"><i data-lucide="phone" class="w-3 h-3"></i></div>
                    <span class="text-slate-700 font-medium truncate">{{ $patient['phone'] ?? 'No contact info' }}</span>
                </div>
                
                <div class="flex items-center text-sm">
                    <div class="w-6 h-6 rounded-lg bg-white flex items-center justify-center mr-3 shadow-sm border border-slate-100 text-slate-400"><i data-lucide="calendar" class="w-3 h-3"></i></div>
                    <span class="text-slate-700 font-medium">
                        {{ !empty($patient['date_of_birth']) ? \Carbon\Carbon::parse($patient['date_of_birth'])->format('d M Y') : 'DOB Unknown' }} 
                        @if(!empty($patient['date_of_birth']))
                            <span class="text-slate-400 text-xs ml-1">({{ \Carbon\Carbon::parse($patient['date_of_birth'])->age }}y)</span>
                        @endif
                    </span>
                </div>

                <div class="flex items-center text-sm">
                    <div class="w-6 h-6 rounded-lg bg-white flex items-center justify-center mr-3 shadow-sm border border-slate-100 text-slate-400"><i data-lucide="user" class="w-3 h-3"></i></div>
                    <span class="text-slate-700 font-medium capitalize">{{ $patient['gender'] ?? 'Gender Not specified' }}</span>
                </div>
            </div>

            <!-- Action -->
            <div class="p-4 border-t border-slate-100 mt-auto bg-white">
                <a href="{{ route('doctor.patients.show', $patient['id']) }}" class="w-full flex items-center justify-center space-x-2 py-2.5 bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white border border-blue-100 hover:border-blue-600 text-sm font-bold rounded-xl transition-all group-hover:shadow-lg group-hover:shadow-blue-500/20">
                    <span>View Clinical Profile</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-3xl border border-slate-100 shadow-sm p-16 text-center">
            <div class="w-24 h-24 mx-auto bg-slate-50 rounded-full flex items-center justify-center mb-6 border border-dashed border-slate-200">
                <i data-lucide="users" class="w-10 h-10 text-slate-300"></i>
            </div>
            <h4 class="text-xl font-bold text-slate-800 mb-2">No Patients Found</h4>
            <p class="text-sm text-slate-500 max-w-md mx-auto leading-relaxed">There are currently no patients assigned to you or registered in the system.</p>
        </div>
        @endforelse
    </div>
    
    <!-- Pagination (Mock) -->
    @if(count($patients) > 0)
    <div class="flex items-center justify-center mt-8">
        <div class="flex items-center space-x-2">
            <button class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors disabled:opacity-50" disabled>
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </button>
            <button class="w-10 h-10 rounded-xl bg-blue-600 border border-blue-600 flex items-center justify-center text-white font-bold shadow-md shadow-blue-500/20">1</button>
            <button class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 font-bold transition-colors">2</button>
            <button class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 font-bold transition-colors">3</button>
            <span class="text-slate-400 px-1">...</span>
            <button class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </button>
        </div>
    </div>
    @endif
</div>
@endsection
