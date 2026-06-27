@extends('layouts.dashboard')

@section('title', 'Patients Directory')

@section('dashboard_content')
<div class="max-w-7xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-4">
        <div class="flex-1">
            <div class="flex items-center space-x-4 mb-3">
                <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-2xl shadow-md shadow-emerald-500/20">
                    <i data-lucide="users" class="w-7 h-7"></i>
                </div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">Patients Directory</h2>
            </div>
            <p class="text-slate-500 text-base max-w-2xl leading-relaxed ml-1">
                Directory of all registered patients in the system. View and manage patient contact details and demographics.
            </p>
        </div>
    </div>

    <!-- Patients Table -->
    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="overflow-x-auto hide-scrollbar">
            <table class="w-full text-left whitespace-nowrap">
                <thead class="bg-slate-50/80 text-slate-500 text-[11px] uppercase tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="px-7 py-5 font-extrabold sticky top-0 bg-slate-50/80 z-10 backdrop-blur-sm">Patient</th>
                        <th class="px-6 py-5 font-extrabold sticky top-0 bg-slate-50/80 z-10 backdrop-blur-sm">Contact</th>
                        <th class="px-6 py-5 font-extrabold sticky top-0 bg-slate-50/80 z-10 backdrop-blur-sm">Gender</th>
                        <th class="px-6 py-5 font-extrabold sticky top-0 bg-slate-50/80 z-10 backdrop-blur-sm">DOB</th>
                        <th class="px-7 py-5 font-extrabold sticky top-0 bg-slate-50/80 z-10 backdrop-blur-sm">Emergency Contact</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 bg-white">
                    @forelse($patients as $patient)
                    <tr class="hover:bg-slate-50/80 transition-colors duration-200 group">
                        <td class="px-7 py-5">
                            <div class="flex items-center space-x-4">
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 text-emerald-700 flex items-center justify-center font-black text-lg shrink-0 shadow-inner">
                                    {{ strtoupper(substr($patient['user']['full_name'] ?? $patient['full_name'] ?? $patient['name'] ?? 'P', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900 text-sm mb-0.5 group-hover:text-emerald-600 transition-colors">{{ $patient['user']['full_name'] ?? $patient['full_name'] ?? $patient['name'] ?? 'Unknown' }}</div>
                                    <div class="text-[11px] uppercase font-bold text-slate-400 tracking-wider flex items-center gap-1.5">
                                        <i data-lucide="fingerprint" class="w-3 h-3"></i>
                                        ID: {{ str_pad($patient['id'] ?? 0, 4, '0', STR_PAD_LEFT) }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="text-sm font-semibold text-slate-700 flex flex-col space-y-1">
                                <span class="flex items-center gap-1.5"><i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i> {{ $patient['phone'] ?? '-' }}</span>
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status: <span class="{{ ($patient['status'] ?? 'normal') == 'critical' ? 'text-rose-500' : 'text-emerald-500' }}">{{ ucfirst($patient['status'] ?? 'normal') }}</span></span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm font-bold text-slate-600">
                            {{ ucfirst($patient['gender'] ?? '-') }}
                        </td>
                        <td class="px-6 py-5 text-sm font-medium text-slate-600">
                            @if(!empty($patient['date_of_birth']))
                                <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-100/80 text-slate-700 text-xs font-bold border border-slate-200/50">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-blue-500"></i>
                                    <span>{{ \Carbon\Carbon::parse($patient['date_of_birth'])->format('d M Y') }}</span>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-7 py-5">
                            <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-rose-50/50 text-rose-700 text-xs font-bold border border-rose-100/50">
                                <i data-lucide="phone-call" class="w-3.5 h-3.5 text-rose-500"></i>
                                <span>{{ $patient['emergency_contact'] ?? 'Not provided' }}</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-24 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-5 border border-slate-100">
                                    <i data-lucide="users-x" class="w-10 h-10 text-slate-300"></i>
                                </div>
                                <p class="font-bold text-lg text-slate-700 mb-1">No Patients Found</p>
                                <p class="text-sm text-slate-400 max-w-sm">There are no patients registered in the directory yet.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endsection
