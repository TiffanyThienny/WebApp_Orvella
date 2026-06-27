@extends('layouts.dashboard')

@section('title', 'Manage Doctor Schedules - Orvella Admin')

@section('dashboard_content')
<div class="max-w-7xl mx-auto space-y-8" x-data="scheduleManager()">
    <!-- Header Section with Summary Cards -->
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-8 mb-4">
        <div class="flex-1">
            <div class="flex items-center space-x-4 mb-3">
                <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-2xl shadow-md shadow-blue-500/20">
                    <i data-lucide="calendar-clock" class="w-7 h-7"></i>
                </div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">Doctor Schedules</h2>
            </div>
            <p class="text-slate-500 text-base max-w-2xl leading-relaxed ml-1">
                Manage physician availability, define time slots, and oversee patient appointments.
            </p>
        </div>
        
        <div class="flex gap-4 overflow-x-auto pb-2 -mx-4 px-4 lg:mx-0 lg:px-0 lg:pb-0 hide-scrollbar w-full lg:w-auto">
            <!-- Stat Cards -->
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 p-5 flex items-center space-x-5 min-w-[220px] lg:min-w-[200px]">
                <div class="w-14 h-14 rounded-2xl bg-blue-50/80 flex items-center justify-center text-blue-600 shadow-inner">
                    <i data-lucide="calendar-check" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Active Schedules</p>
                    <p class="text-2xl font-black text-slate-800">{{ $stats['total_active'] ?? 0 }}</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 p-5 flex items-center space-x-5 min-w-[220px] lg:min-w-[200px]">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50/80 flex items-center justify-center text-emerald-600 shadow-inner">
                    <i data-lucide="stethoscope" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Doctors Today</p>
                    <p class="text-2xl font-black text-slate-800 flex items-baseline gap-1">
                        {{ $stats['doctors_today'] ?? 0 }} <span class="text-sm font-bold text-slate-400">/ {{ $stats['total_doctors'] ?? 0 }}</span>
                    </p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 p-5 flex items-center space-x-5 min-w-[220px] lg:min-w-[200px]">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50/80 flex items-center justify-center text-indigo-600 shadow-inner">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Slots</p>
                    <p class="text-2xl font-black text-slate-800">{{ $stats['total_slots'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert / Notifications (Toast) -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-3">
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 shadow-xl shadow-emerald-500/10 text-emerald-800 px-6 py-4 rounded-2xl flex items-center space-x-3 transform transition-all duration-500 ease-out" id="flash-success">
                <div class="bg-emerald-100 rounded-full p-1.5 shrink-0">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <span class="font-medium text-sm">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 shadow-xl shadow-rose-500/10 text-rose-800 px-6 py-4 rounded-2xl flex items-center space-x-3 transform transition-all duration-500 ease-out" id="flash-error">
                <div class="bg-rose-100 rounded-full p-1.5 shrink-0">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600"></i>
                </div>
                <span class="font-medium text-sm">{{ session('error') }}</span>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left Column: Create Schedule Form -->
        <div class="lg:col-span-4 xl:col-span-4 sticky top-6">
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
                <div class="px-7 py-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/30">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i data-lucide="plus" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg tracking-tight">Create Schedule</h3>
                            <p class="text-xs text-slate-500 font-medium">Add physician availability</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.schedules.assign') }}" method="POST" class="p-7 space-y-7" @submit="validateCreateForm($event)">
                    @csrf
                    
                    <!-- Doctor Selection -->
                    <div class="space-y-2.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Physician <span class="text-rose-500">*</span></label>
                        <div class="relative group">
                            <i data-lucide="user" class="absolute left-4 top-3.5 w-5 h-5 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                            <select name="doctor_id" required class="w-full pl-12 pr-10 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700 appearance-none cursor-pointer">
                                <option value="">Select Doctor...</option>
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc['id'] }}">{{ $doc['full_name'] ?? $doc['username'] }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-3.5 w-5 h-5 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-2 gap-5">
                        <div class="space-y-2.5">
                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Start Date <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="date" name="start_date" x-model="createForm.start_date" :min="todayStr" @change="if(createForm.end_date < createForm.start_date) createForm.end_date = createForm.start_date" required class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700 cursor-pointer">
                            </div>
                        </div>
                        <div class="space-y-2.5">
                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">End Date <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="date" name="end_date" x-model="createForm.end_date" :min="createForm.start_date" required class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700 cursor-pointer">
                            </div>
                        </div>
                    </div>

                    <!-- Time Frame -->
                    <div class="grid grid-cols-2 gap-5">
                        <div class="space-y-2.5">
                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Start Time <span class="text-rose-500">*</span></label>
                            <input type="time" name="start_time" x-model="createForm.start_time" @change="$el.blur()" required class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700 cursor-pointer">
                        </div>
                        <div class="space-y-2.5">
                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">End Time <span class="text-rose-500">*</span></label>
                            <input type="time" name="end_time" x-model="createForm.end_time" @change="$el.blur()" required class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700 cursor-pointer">
                        </div>
                    </div>

                    <!-- Quota -->
                    <div class="space-y-2.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Quota Slots <span class="text-rose-500">*</span></label>
                        <input type="number" name="max_patients" x-model="createForm.max_patients" required min="1" max="100" class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700">
                        <input type="hidden" name="is_available" value="1">
                    </div>

                    <div class="pt-4">
                        <button type="submit" :disabled="isCreating" class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 text-white text-sm font-bold rounded-2xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/40 transition-all duration-300 flex items-center justify-center space-x-2 transform hover:-translate-y-0.5">
                            <svg x-show="isCreating" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <i x-show="!isCreating" data-lucide="check-circle" class="w-5 h-5"></i>
                            <span x-text="isCreating ? 'Creating Schedule...' : 'Assign Schedule'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Current Schedules List -->
        <div class="lg:col-span-8 xl:col-span-8 space-y-6">
            <!-- Search & Filters -->
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-3 flex flex-col sm:flex-row gap-4 justify-between items-center transition-all duration-300 focus-within:shadow-md focus-within:border-blue-200">
                <div class="relative w-full sm:w-96">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400"></i>
                    <input type="text" x-model="searchQuery" placeholder="Search by physician name..." class="w-full pl-12 pr-4 py-3 bg-transparent border-none focus:ring-0 outline-none text-sm font-medium text-slate-700 placeholder-slate-400">
                </div>
                <div class="w-px h-8 bg-slate-200 hidden sm:block"></div>
                <div class="flex items-center w-full sm:w-auto px-2">
                    <select x-model="filterStatus" class="w-full sm:w-auto px-4 py-2 bg-transparent border-none text-sm font-bold text-slate-600 focus:outline-none cursor-pointer appearance-none">
                        <option value="all">All Status</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
                <div class="overflow-x-auto hide-scrollbar">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/80 text-slate-500 text-[11px] uppercase tracking-widest border-b border-slate-100">
                            <tr>
                                <th class="px-7 py-5 font-extrabold sticky top-0 bg-slate-50/80 z-10 backdrop-blur-sm">Physician</th>
                                <th class="px-6 py-5 font-extrabold sticky top-0 bg-slate-50/80 z-10 backdrop-blur-sm whitespace-nowrap">Date Range</th>
                                <th class="px-6 py-5 font-extrabold sticky top-0 bg-slate-50/80 z-10 backdrop-blur-sm whitespace-nowrap">Hours</th>
                                <th class="px-6 py-5 font-extrabold sticky top-0 bg-slate-50/80 z-10 backdrop-blur-sm text-center">Slots</th>
                                <th class="px-7 py-5 font-extrabold sticky top-0 bg-slate-50/80 z-10 backdrop-blur-sm text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                            <template x-for="sched in filteredSchedules" :key="sched.id">
                                <tr class="hover:bg-slate-50/80 transition-colors duration-200 group">
                                    <td class="px-7 py-5">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-100 to-blue-100 text-indigo-700 flex items-center justify-center font-black text-lg shrink-0 shadow-inner">
                                                <span x-text="sched.doctor_name.charAt(0).toUpperCase()"></span>
                                            </div>
                                            <div class="font-bold text-slate-900 text-sm" x-text="sched.doctor_name"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="text-sm font-semibold text-slate-700 flex flex-col space-y-1">
                                            <span x-text="formatDate(sched.appointment_date)"></span>
                                            <template x-if="sched.end_date && sched.end_date !== sched.appointment_date">
                                                <span class="text-[11px] font-bold text-slate-400 flex items-center gap-1">
                                                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                                    <span class="text-slate-600" x-text="formatDate(sched.end_date)"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl bg-slate-100/80 text-slate-700 text-xs font-bold border border-slate-200/50">
                                            <i data-lucide="clock" class="w-3.5 h-3.5 text-blue-500"></i>
                                            <span x-text="formatTime(sched.start_time) + ' - ' + formatTime(sched.end_time)"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-slate-50 border border-slate-200 text-sm font-black text-slate-700" x-text="sched.max_patients"></span>
                                    </td>
                                    <td class="px-7 py-5">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button @click="openEditModal(sched)" class="p-2.5 text-blue-600 hover:bg-blue-50 hover:text-blue-700 rounded-xl transition-colors" title="Edit Schedule">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                            <div class="relative">
                                                <button @click.stop="confirmDeleteId = (confirmDeleteId === sched.id ? null : sched.id)"
                                                        class="p-2.5 text-rose-500 hover:bg-rose-50 hover:text-rose-600 rounded-xl transition-colors" title="Delete Schedule">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                                <div x-show="confirmDeleteId === sched.id"
                                                     @click.outside="confirmDeleteId = null"
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                                     style="display:none"
                                                     class="absolute right-0 top-full mt-2 z-50 bg-white border border-rose-100 shadow-2xl shadow-rose-500/10 rounded-2xl p-4 w-52 text-left">
                                                    <p class="text-xs font-black text-slate-800 mb-1">Delete this schedule?</p>
                                                    <p class="text-[11px] text-slate-500 mb-3 leading-relaxed">This action cannot be undone.</p>
                                                    <div class="flex gap-2">
                                                        <button type="button" @click="confirmDeleteId = null"
                                                                class="flex-1 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-colors">
                                                            Cancel
                                                        </button>
                                                        <button type="button" @click="deleteSchedule(sched.id)"
                                                                class="flex-1 px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-colors">
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            
                            <template x-if="filteredSchedules.length === 0">
                                <tr>
                                    <td colspan="5" class="px-6 py-24 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-5 border border-slate-100">
                                                <i data-lucide="calendar-x" class="w-10 h-10 text-slate-300"></i>
                                            </div>
                                            <p class="font-bold text-lg text-slate-700 mb-1">No Schedules Found</p>
                                            <p class="text-sm text-slate-400 max-w-sm">Adjust your search or filters, or create a new schedule on the left panel.</p>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div x-show="isEditModalOpen" 
         style="display: none;"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="closeEditModal()"></div>
        
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-2xl w-full relative z-10 overflow-hidden flex flex-col max-h-[90vh]"
             x-transition:enter="transition ease-out duration-400"
             x-transition:enter-start="opacity-0 translate-y-12 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-12 sm:translate-y-0 sm:scale-95">
            
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between bg-white relative z-10">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i data-lucide="edit-3" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-xl tracking-tight">Edit Schedule</h3>
                        <p class="text-xs text-slate-500 font-medium">Update physician availability slots</p>
                    </div>
                </div>
                <button type="button" @click="closeEditModal()" class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="overflow-y-auto hide-scrollbar p-8 bg-slate-50/30">
                <form :action="'{{ url('admin/schedules') }}/' + editForm.id" method="POST" id="editScheduleForm" @submit="isUpdating = true">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="doctor_id" x-model="editForm.doctor_id">
                    
                    <div class="space-y-8">
                        <!-- Premium Physician Card -->
                        <div class="p-5 bg-gradient-to-r from-blue-50 to-indigo-50/50 border border-blue-100/50 rounded-2xl flex items-center space-x-5 shadow-sm">
                            <div class="w-14 h-14 rounded-2xl bg-white text-blue-600 flex items-center justify-center font-black text-xl shrink-0 shadow-sm border border-blue-50">
                                <span x-text="editForm.doctor_name ? editForm.doctor_name.charAt(0).toUpperCase() : ''"></span>
                            </div>
                            <div>
                                <p class="text-[10px] text-blue-500 font-extrabold uppercase tracking-widest mb-1">Assigned Physician</p>
                                <p class="font-black text-slate-800 text-lg" x-text="editForm.doctor_name"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2.5">
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Start Date</label>
                                <div class="relative">
                                    <input type="date" name="start_date" x-model="editForm.start_date" required class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700 shadow-sm cursor-pointer">
                                </div>
                            </div>
                            <div class="space-y-2.5">
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">End Date</label>
                                <div class="relative">
                                    <input type="date" name="end_date" x-model="editForm.end_date" :min="editForm.start_date" required class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700 shadow-sm cursor-pointer">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2.5">
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Start Time</label>
                                <div class="relative">
                                    <input type="time" name="start_time" x-model="editForm.start_time" @change="$el.blur()" required class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700 shadow-sm cursor-pointer">
                                </div>
                            </div>
                            <div class="space-y-2.5">
                                <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">End Time</label>
                                <div class="relative">
                                    <input type="time" name="end_time" x-model="editForm.end_time" @change="$el.blur()" required class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700 shadow-sm cursor-pointer">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            <label class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">Quota Slots</label>
                            <input type="number" name="max_patients" x-model="editForm.max_patients" required min="1" max="100" class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold text-slate-700 shadow-sm">
                            <input type="hidden" name="is_available" value="1">
                        </div>
                    </div>
                </form>
            </div>

            <div class="px-8 py-5 border-t border-slate-100 bg-white flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                <button type="button" @click="closeEditModal()" class="w-full sm:w-auto px-6 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold rounded-xl transition-all duration-300">
                    Cancel
                </button>
                <button type="submit" form="editScheduleForm" :disabled="isUpdating" class="w-full sm:w-auto px-8 py-3.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/40 transition-all duration-300 flex items-center justify-center space-x-2">
                    <svg x-show="isUpdating" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="isUpdating ? 'Saving Changes...' : 'Save Changes'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
function scheduleManager() {
    const formatStrTime = (timeStr) => {
        if (!timeStr) return '';
        return timeStr.substring(0, 5);
    };

    const getTodayStr = () => new Date().toISOString().split('T')[0];
    
    // Get current time to nearest 30 mins
    const getNowTimeStr = () => {
        let d = new Date();
        let h = d.getHours().toString().padStart(2, '0');
        return `${h}:00`;
    };
    
    const getNextTimeStr = () => {
        let d = new Date();
        let h = (d.getHours() + 1).toString().padStart(2, '0');
        return `${h}:00`;
    };

    return {
        schedules: @json($schedules ?? []),
        searchQuery: '',
        filterStatus: 'all',
        confirmDeleteId: null,
        todayStr: getTodayStr(),
        
        isCreating: false,
        createForm: {
            start_date: getTodayStr(),
            end_date: getTodayStr(),
            start_time: getNowTimeStr(),
            end_time: getNextTimeStr(),
            max_patients: 10,
            is_available: true
        },

        isEditModalOpen: false,
        isUpdating: false,
        editForm: {},

        get filteredSchedules() {
            let result = [...this.schedules];
            
            // Search
            if (this.searchQuery) {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(s => s.doctor_name.toLowerCase().includes(q));
            }
            
            // Status Filter
            if (this.filterStatus === 'active') {
                result = result.filter(s => s.is_available);
            } else if (this.filterStatus === 'inactive') {
                result = result.filter(s => !s.is_available);
            }
            
            // Sort newest first by appointment_date
            result.sort((a, b) => new Date(b.appointment_date || 0) - new Date(a.appointment_date || 0));
            
            return result;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        formatTime: formatStrTime,

        openEditModal(sched) {
            let startDate = sched.appointment_date ? sched.appointment_date.split('T')[0] : '';
            let endDate = sched.end_date ? sched.end_date.split('T')[0] : startDate;

            this.editForm = {
                id: sched.id,
                doctor_id: sched.doctor_id,
                doctor_name: sched.doctor_name,
                start_date: startDate,
                end_date: endDate,
                start_time: formatStrTime(sched.start_time),
                end_time: formatStrTime(sched.end_time),
                max_patients: sched.max_patients,
                is_available: sched.is_available
            };
            this.isEditModalOpen = true;
            document.body.style.overflow = 'hidden'; // Prevent background scroll
        },

        closeEditModal() {
            this.isEditModalOpen = false;
            document.body.style.overflow = '';
            setTimeout(() => {
                this.editForm = {};
                this.isUpdating = false;
            }, 300);
        },

        validateCreateForm(event) {
            if (this.createForm.start_date === this.createForm.end_date) {
                if (this.createForm.start_time >= this.createForm.end_time) {
                    event.preventDefault();
                    alert('Jika start date dan end date sama, start time harus lebih awal dari end time.');
                    return;
                }
            }
            this.isCreating = true;
        },

        deleteSchedule(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("admin/schedules") }}/' + id;
            form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        },

        init() {
            // Auto dismiss toast
            setTimeout(() => {
                ['flash-success', 'flash-error'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.classList.add('opacity-0', 'translate-y-2');
                        setTimeout(() => el.remove(), 500);
                    }
                });
            }, 4000);
            
            // Reinitialize lucide icons when schedule changes
            this.$watch('searchQuery', () => setTimeout(() => { if (typeof lucide !== "undefined") lucide.createIcons(); }, 50));
            this.$watch('filterStatus', () => setTimeout(() => { if (typeof lucide !== "undefined") lucide.createIcons(); }, 50));
        }
    }
}

// Reinitialize lucide icons after Alpine updates
document.addEventListener('alpine:initialized', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
@endsection
