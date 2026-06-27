@extends('layouts.app')

@section('content')
@php
    $user = session('user') ?? [];
    $role = strtolower($user['role_name'] ?? $user['role'] ?? '');
@endphp
<div class="flex h-screen bg-slate-50 overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col shadow-2xl relative z-20 border-r border-slate-800">
        <div class="absolute inset-0 bg-gradient-to-b from-blue-900/20 to-transparent pointer-events-none"></div>
        <div class="p-5 relative z-10 flex items-center space-x-3 border-b border-slate-800/60">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-400 flex items-center justify-center shadow-lg shadow-blue-500/30">
                @if($role == 'doctor')
                    <i data-lucide="activity" class="w-5 h-5 text-white"></i>
                @else
                    <i data-lucide="stethoscope" class="w-5 h-5 text-white"></i>
                @endif
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight text-white leading-tight">Orvella</h1>
                <p class="text-[9px] text-blue-300 font-extrabold uppercase tracking-widest">Healthcare</p>
            </div>
        </div>
        
        <nav class="flex-1 px-3 space-y-1 py-4 overflow-y-auto custom-scrollbar">
            @if($role == 'admin')
                <x-nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')" icon="layout-dashboard">Dashboard</x-nav-link>
                <div class="pt-4 pb-1.5 text-[10px] font-black text-slate-500 uppercase tracking-widest px-3.5 flex items-center space-x-2">
                    <span>Management</span>
                    <div class="flex-1 h-px bg-slate-800/60"></div>
                </div>
                <x-nav-link href="{{ route('admin.users.patients') }}" :active="request()->routeIs('admin.users.patients')" icon="users">Patients</x-nav-link>
                <x-nav-link href="{{ route('admin.users.doctors') }}" :active="request()->routeIs('admin.users.doctors')" icon="stethoscope">Doctors</x-nav-link>
                <x-nav-link href="{{ route('admin.users.medrec') }}" :active="request()->routeIs('admin.users.medrec')" icon="clipboard-list">Medrec Staff</x-nav-link>
                <x-nav-link href="{{ route('admin.schedules') }}" :active="request()->routeIs('admin.schedules')" icon="calendar-days">Doctor Schedules</x-nav-link>
                <div class="pt-4 pb-1.5 text-[10px] font-black text-slate-500 uppercase tracking-widest px-3.5 flex items-center space-x-2">
                    <span>Configuration</span>
                    <div class="flex-1 h-px bg-slate-800/60"></div>
                </div>
                <x-nav-link href="{{ route('admin.settings') }}" :active="request()->routeIs('admin.settings')" icon="settings">Settings</x-nav-link>
                <x-nav-link href="{{ route('admin.site-config') }}" :active="request()->routeIs('admin.site-config')" icon="wrench">Site Config</x-nav-link>
            @elseif($role == 'doctor')
                <x-nav-link href="{{ route('doctor.dashboard') }}" :active="request()->routeIs('doctor.dashboard')" icon="layout-dashboard">Dashboard</x-nav-link>
                <div class="pt-4 pb-1.5 text-[10px] font-black text-slate-500 uppercase tracking-widest px-3.5 flex items-center space-x-2">
                    <span>Clinical</span>
                    <div class="flex-1 h-px bg-slate-800/60"></div>
                </div>
                <x-nav-link href="{{ route('doctor.patients.index') }}" :active="request()->routeIs('doctor.patients.*')" icon="users">My Patients</x-nav-link>
                <x-nav-link href="{{ route('doctor.appointments') }}" :active="request()->routeIs('doctor.appointments')" icon="calendar-days">Appointments</x-nav-link>
                <div class="pt-4 pb-1.5 text-[10px] font-black text-slate-500 uppercase tracking-widest px-3.5 flex items-center space-x-2">
                    <span>Queue</span>
                    <div class="flex-1 h-px bg-slate-800/60"></div>
                </div>
                <x-nav-link href="{{ route('doctor.scans.queue') }}" :active="request()->routeIs('doctor.scans.queue')" icon="file-heart">Diagnostic Queue</x-nav-link>
            @elseif($role == 'medical record')
                <x-nav-link href="{{ route('medrec.dashboard') }}" :active="request()->routeIs('medrec.dashboard')" icon="layout-dashboard">Dashboard</x-nav-link>
                <div class="pt-4 pb-1.5 text-[10px] font-black text-slate-500 uppercase tracking-widest px-3.5 flex items-center space-x-2">
                    <span>Diagnostics</span>
                    <div class="flex-1 h-px bg-slate-800/60"></div>
                </div>
                <x-nav-link href="{{ route('medrec.upload') }}" :active="request()->routeIs('medrec.upload')" icon="upload-cloud">Upload Scan</x-nav-link>
                <x-nav-link href="{{ route('medrec.scans') }}" :active="request()->routeIs('medrec.scans')" icon="file-heart">Scan History</x-nav-link>
            @elseif($role == 'patient')
                <x-nav-link href="{{ route('patient.dashboard') }}" :active="request()->routeIs('patient.dashboard')" icon="layout-dashboard">Dashboard</x-nav-link>
                <div class="pt-4 pb-1.5 text-[10px] font-black text-slate-500 uppercase tracking-widest px-3.5 flex items-center space-x-2">
                    <span>My Health</span>
                    <div class="flex-1 h-px bg-slate-800/60"></div>
                </div>
                <x-nav-link href="{{ route('patient.results') }}" :active="request()->routeIs('patient.results')" icon="clipboard-check">My Results</x-nav-link>
                <x-nav-link href="{{ route('patient.appointments') }}" :active="request()->routeIs('patient.appointments')" icon="calendar-days">Appointments</x-nav-link>
            @endif
        </nav>

        <!-- Sidebar Footer Profile & Logout -->
        <div class="p-4 border-t border-slate-800 relative z-10 bg-slate-950/40 backdrop-blur-md">
            <div class="flex items-center justify-between px-1 py-0.5">
                <div class="flex items-center space-x-2.5 overflow-hidden">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center font-bold text-sm shadow-inner flex-shrink-0 text-white border border-blue-400/20">
                        {{ substr($user['full_name'] ?? 'U', 0, 1) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold truncate text-slate-200 leading-tight">{{ $user['full_name'] ?? 'User' }}</p>
                        <p class="text-[10px] text-blue-400 font-medium truncate capitalize leading-tight mt-0.5">{{ $role }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="flex-shrink-0">
                    @csrf
                    <button type="submit" class="p-2 bg-slate-800/80 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 rounded-lg border border-slate-700/50 hover:border-rose-500/30 transition-all shadow-sm" title="Sign Out">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50 relative">
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-xl border-b border-slate-200 h-16 flex items-center justify-between px-6 lg:px-8 flex-shrink-0 sticky top-0 z-10">
            <h2 class="text-lg font-black text-slate-800 flex items-center space-x-2">
                @yield('title', 'Dashboard')
            </h2>
            
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3 hover:bg-slate-50 py-1.5 px-3 rounded-2xl border border-slate-100 transition-colors">
                    <div class="text-right hidden sm:block">
                        <span class="block text-xs font-black text-slate-800 leading-tight">{{ $user['full_name'] ?? 'User' }}</span>
                        <span class="block text-[10px] font-bold text-slate-400 capitalize leading-tight mt-0.5">{{ $role }}</span>
                    </div>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold border border-blue-100 text-sm shadow-inner flex-shrink-0">
                        {{ substr($user['full_name'] ?? 'U', 0, 1) }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 relative custom-scrollbar">
            <!-- Decorative background elements -->
            <div class="absolute top-0 left-0 w-full h-64 bg-gradient-to-b from-blue-50/50 to-transparent pointer-events-none -z-10"></div>
            
            @yield('dashboard_content')
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</div>
@endsection
