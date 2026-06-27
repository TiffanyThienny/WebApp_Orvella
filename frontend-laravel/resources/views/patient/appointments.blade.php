@extends('layouts.dashboard')

@section('title', 'Clinical Scheduling & Slot Booking')

@section('dashboard_content')
<div class="max-w-7xl mx-auto space-y-8" x-data="patientAppointments()">

    <!-- Modern Header & Information Banner -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 lg:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-96 h-96 bg-gradient-to-bl from-blue-50 via-transparent to-transparent rounded-bl-full pointer-events-none opacity-60"></div>
        
        <div class="relative z-10">
            <div class="flex items-center space-x-3 mb-2">
                <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-xl text-xs font-black uppercase tracking-widest border border-blue-100">Daily Quota Booking</span>
                <span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-xl text-xs font-bold border border-emerald-100" x-text="'Max ' + getTodayMaxSlots() + ' Slots / Day per Doctor'"></span>
            </div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Physician Consultation Slots</h2>
            <p class="text-slate-500 text-sm mt-1 max-w-2xl leading-relaxed">
                Choose a day to reserve your daily medical consultation slot. <span class="font-bold text-slate-700">Booking is only to reserve a daily consultation slot, not a queue number. Patients can arrive anytime between 09:00 - 17:00.</span> Even with a slot, you may still need to wait if there is another patient being examined.
            </p>
        </div>

        <div class="flex items-center space-x-4 relative z-10 bg-slate-50 p-4 rounded-2xl border border-slate-100 shadow-inner flex-shrink-0">
            <div class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center font-bold text-xl shadow-lg shadow-blue-500/30">
                <i data-lucide="calendar-check-2" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Your Bookings</p>
                <p class="text-2xl font-black text-slate-800">{{ count($appointments) }} Scheduled</p>
            </div>
        </div>
    </div>

    <!-- Error/Notice Toast -->
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 p-5 rounded-3xl shadow-sm flex items-start space-x-4 animate-fade-in" x-data="{ show: true }" x-show="show">
            <div class="w-10 h-10 bg-rose-600 text-white rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-rose-500/30 mt-0.5">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-extrabold text-rose-900">Booking Notice</h4>
                <p class="text-xs text-rose-700 mt-1 font-medium leading-relaxed">{{ session('error') }}</p>
            </div>
            <button @click="show = false" class="text-rose-500 hover:text-rose-700 p-1"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
    @endif

    <!-- Upper Stats Widget Area -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Upcoming Consultation Card -->
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-3xl p-6 text-white shadow-xl relative overflow-hidden">
            <div class="absolute right-0 top-0 w-32 h-32 bg-white/10 rounded-full blur-2xl transform translate-x-1/3 -translate-y-1/3"></div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-blue-100">Next Upcoming Consultation</h4>
            @php
                $upcoming = collect($appointments)->where('status', 'approved')->first();
            @endphp
            @if($upcoming)
                <div class="mt-4 space-y-2">
                    <p class="text-2xl font-black">Dr. {{ $upcoming['doctor_name'] ?? $upcoming['doctor']['full_name'] ?? 'Specialist' }}</p>
                    <div class="flex items-center space-x-4 text-xs font-bold text-blue-50 bg-white/10 p-2.5 rounded-xl border border-white/10">
                        <span class="flex items-center"><i data-lucide="calendar" class="w-4 h-4 mr-1"></i> {{ \Carbon\Carbon::parse($upcoming['appointment_date'])->format('d M Y') }}</span>
                        <span class="flex items-center"><i data-lucide="clock" class="w-4 h-4 mr-1"></i> {{ \Carbon\Carbon::parse($upcoming['appointment_date'])->format('H:i') }}</span>
                    </div>
                </div>
            @else
                <p class="text-base font-bold text-blue-100 mt-4 italic">No approved upcoming consultations scheduled.</p>
            @endif
        </div>

        <!-- Remaining Quota Indicator -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex flex-col justify-between">
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Selected Doctor's Quota Today</h4>
                <div class="flex items-baseline space-x-2 mt-2">
                    <span class="text-3xl font-black text-slate-800" x-text="getTodaySlotsRemaining()"></span>
                    <span class="text-xs text-slate-500 font-bold">slots available today</span>
                </div>
            </div>
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden mt-4">
                <div class="h-full bg-emerald-500 transition-all duration-500" :style="'width: ' + ((getTodaySlotsRemaining() / getTodayMaxSlots()) * 100) + '%'"></div>
            </div>
        </div>

        <!-- Booking Summary Card -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm flex flex-col justify-between">
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Consultation History</h4>
                <div class="flex items-baseline space-x-2 mt-2">
                    <span class="text-3xl font-black text-slate-800">{{ count($appointments) }}</span>
                    <span class="text-xs text-slate-500 font-bold">total requests registered</span>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 font-medium leading-relaxed">Includes pending approvals, diagnostic clearances, and past completed visits.</p>
        </div>
    </div>

    <!-- Booking Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left: Booking Form -->
        <div class="lg:col-span-4 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 bg-gradient-to-br from-slate-900 to-slate-850 text-white relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl transform translate-x-1/2 -translate-y-1/2"></div>
                    <div class="relative z-10 flex items-center space-x-3">
                        <div class="p-2.5 bg-white/5 rounded-xl border border-white/10"><i data-lucide="calendar-plus" class="w-6 h-6 text-blue-400"></i></div>
                        <div>
                            <h3 class="font-black text-lg tracking-tight">Request Slot</h3>
                            <p class="text-xs text-slate-300 mt-0.5 font-medium">Auto-move active if full</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('patient.appointments.book') }}" method="POST" @submit="isSubmitting = true" class="p-6 space-y-6">
                    @csrf
                    
                    <!-- Doctor Selection -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center">
                            <i data-lucide="stethoscope" class="w-3.5 h-3.5 mr-1.5 text-blue-600"></i> Attending Specialist <span class="text-red-500 ml-0.5">*</span>
                        </label>
                        <div class="relative group">
                            <i data-lucide="user" class="absolute left-4 top-3.5 w-4 h-4 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                            <select name="doctor_id" id="doctor_id" x-model="selectedDoctorId" required class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-bold text-slate-700 appearance-none shadow-sm cursor-pointer hover:bg-slate-100/50">
                                <option value="">-- Choose Doctor --</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor['id'] }}">Dr. {{ $doctor['full_name'] ?? $doctor['username'] ?? 'Specialist' }}</option>
                                @endforeach
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-3.5 w-4 h-4 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Date Selection -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                            <span class="flex items-center"><i data-lucide="calendar" class="w-3.5 h-3.5 mr-1.5 text-emerald-600"></i> Reservation Date <span class="text-red-500 ml-0.5">*</span></span>
                        </label>
                        <div class="relative group">
                            <i data-lucide="calendar-days" class="absolute left-4 top-3.5 w-4 h-4 text-slate-400 group-focus-within:text-emerald-600 transition-colors pointer-events-none"></i>
                            <input type="date" name="appointment_date" id="appointment_date" x-model="bookingDate" @change="updateAvailableTimes" required min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-emerald-600/20 focus:border-emerald-600 outline-none transition-all text-xs font-bold text-slate-700 shadow-sm cursor-pointer hover:bg-slate-100/50">
                        </div>
                        <div x-show="dateError" class="text-rose-500 font-semibold text-[11px] flex items-start mt-1 gap-1">
                            <span class="mt-0.5">•</span>
                            <span x-text="dateError"></span>
                        </div>
                    </div>

                    <!-- Time Selection -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                            <span class="flex items-center"><i data-lucide="clock" class="w-3.5 h-3.5 mr-1.5 text-blue-600"></i> Reservation Time <span class="text-red-500 ml-0.5">*</span></span>
                            <span x-show="loadingSchedules" class="flex items-center text-blue-500 font-semibold text-[10px]">
                                <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Loading slots...
                            </span>
                        </label>
                        <div class="relative group">
                            <i data-lucide="clock-3" class="absolute left-4 top-3.5 w-4 h-4 text-slate-400 group-focus-within:text-blue-600 transition-colors pointer-events-none"></i>
                            <select name="appointment_time" required :disabled="loadingSchedules" class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-bold text-slate-700 appearance-none shadow-sm cursor-pointer hover:bg-slate-100/50 disabled:opacity-60 disabled:cursor-wait">
                                <option value="" x-text="loadingSchedules ? 'Loading available times...' : '-- Choose Time --'"></option>
                                <template x-for="time in availableTimes" :key="time.value">
                                    <option :value="time.value" x-text="time.label"></option>
                                </template>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-3.5 w-4 h-4 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center">
                            <i data-lucide="file-text" class="w-3.5 h-3.5 mr-1.5 text-slate-650"></i> Symptoms / Purpose of Visit
                        </label>
                        <textarea name="notes" rows="3" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all text-xs font-medium text-slate-800 placeholder:text-slate-400 resize-none shadow-sm" placeholder="Explain symptoms or purpose briefly..."></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="isSubmitting" class="w-full py-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold rounded-2xl shadow-lg shadow-blue-500/30 transition-all flex items-center justify-center space-x-2 group">
                        <span x-text="isSubmitting ? 'Reserving Your Slot...' : 'Reserve Consultation Slot'"></span>
                        <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Interactive Calendar & Slot Status -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden flex flex-col h-full min-h-[550px]">
                <!-- Tab Controls -->
                <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/50 sticky top-0 z-10">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-blue-100 text-blue-600 rounded-2xl"><i data-lucide="calendar-range" class="w-5 h-5"></i></div>
                        <div>
                            <h3 class="font-extrabold text-slate-800 text-base tracking-tight">Quota Slot Calendar</h3>
                            <template x-if="selectedDoctorId">
                                <p class="text-xs text-blue-600 font-bold mt-0.5" x-text="'Showing schedule for: ' + getSelectedDoctorName()"></p>
                            </template>
                            <template x-if="!selectedDoctorId">
                                <p class="text-xs text-slate-500 font-medium">Select a doctor above to view their availability.</p>
                            </template>
                        </div>
                    </div>

                    <!-- View Toggle -->
                    <div class="flex bg-white p-1 rounded-2xl border border-slate-200 shadow-sm">
                        <button @click="viewMode = 'calendar'" :class="viewMode == 'calendar' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'" class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center space-x-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            <span>Calendar</span>
                        </button>
                        <button @click="viewMode = 'list'" :class="viewMode == 'list' ? 'bg-blue-600 text-white shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'" class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center space-x-1.5">
                            <i data-lucide="list-filter" class="w-3.5 h-3.5"></i>
                            <span>List History</span>
                        </button>
                    </div>
                </div>

                <!-- Calendar View -->
                <div x-show="viewMode == 'calendar'" class="p-6 flex-1 flex flex-col">
                    <!-- Date Headers / Nav -->
                    <div class="flex items-center justify-between mb-6 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <button @click="prevMonth()" class="p-2 hover:bg-white rounded-xl border border-transparent hover:border-slate-200 shadow-sm transition-all text-slate-600"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
                        <h4 class="text-base font-black text-slate-800 uppercase tracking-wider" x-text="monthNames[currentMonth] + ' ' + currentYear"></h4>
                        <button @click="nextMonth()" class="p-2 hover:bg-white rounded-xl border border-transparent hover:border-slate-200 shadow-sm transition-all text-slate-600"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
                    </div>

                    <!-- Calendar Days Grid -->
                    <div class="grid grid-cols-7 gap-3 mb-2 text-center">
                        <template x-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day">
                            <div class="text-xs font-extrabold text-slate-400 uppercase tracking-widest py-2" x-text="day"></div>
                        </template>
                    </div>

                    <div class="grid grid-cols-7 gap-3 flex-1">
                        <!-- Blank spaces -->
                        <template x-for="blank in blankDays" :key="'blank-' + blank">
                            <div class="bg-slate-50/50 rounded-2xl border border-slate-100/50 min-h-[90px]"></div>
                        </template>

                        <!-- Active Days of Month -->
                        <template x-for="day in monthDays" :key="'day-' + day">
                            <div class="bg-white rounded-2xl border p-2 flex flex-col justify-between min-h-[95px] relative group transition-all"
                                 :class="[
                                     isDateUnavailable(day) ? 'bg-slate-50 opacity-40 border-slate-200 cursor-not-allowed pointer-events-none' : '',
                                     isDateFull(day) ? 'bg-rose-50/20 border-rose-200 cursor-not-allowed' : 'shadow-sm border-slate-200 hover:border-blue-400 hover:shadow-md cursor-pointer'
                                 ]"
                                 @click="!isDateUnavailable(day) && selectDate(day)">
                                
                                <div class="flex items-center justify-between">
                                     <span class="text-xs font-bold" :class="isDateSelected(day) ? 'text-blue-600 font-extrabold text-sm' : 'text-slate-600'" x-text="day"></span>
                                     <template x-if="hasOwnAppointment(day)">
                                         <span class="w-2 h-2 rounded-full bg-blue-600 shadow-sm animate-pulse"></span>
                                     </template>
                                </div>

                                <!-- Slot Indicator Text -->
                                <div class="mt-2 text-left" x-show="!isDateUnavailable(day) && selectedDoctorId">
                                    <!-- Dynamic Time Slots -->
                                    <div class="text-[8px] font-semibold text-slate-500 mb-1 flex items-center justify-center bg-slate-100 rounded-md py-0.5">
                                        <i data-lucide="clock" class="w-2.5 h-2.5 mr-0.5 text-slate-400"></i>
                                        <span x-text="formatTimeRange(getDoctorScheduleForDate(getDateString(day)))"></span>
                                    </div>
                                    <template x-if="getSlotsRemaining(day) > 2">
                                        <span class="text-[9px] font-extrabold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-md border border-emerald-100 block w-full text-center" x-text="getSlotsRemaining(day) + ' slot left'"></span>
                                    </template>
                                    <template x-if="getSlotsRemaining(day) == 1 || getSlotsRemaining(day) == 2">
                                        <span class="text-[9px] font-extrabold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md border border-amber-100 block w-full text-center" x-text="getSlotsRemaining(day) + ' slot left'"></span>
                                    </template>
                                    <template x-if="getSlotsRemaining(day) == 0">
                                        <span class="text-[9px] font-black text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded-md border border-rose-100 block w-full text-center">FULL</span>
                                    </template>
                                </div>

                                <!-- User own booked indicator -->
                                <template x-if="hasOwnAppointment(day)">
                                    <div class="mt-1 bg-blue-500 text-white rounded-lg p-1 text-[8px] font-black uppercase text-center truncate">
                                        BOOKED
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- History list View -->
                <div x-show="viewMode == 'list'" class="p-6 flex-1 overflow-y-auto custom-scrollbar" style="display: none;">
                    <div class="space-y-4">
                        @forelse($appointments as $apt)
                            <div class="p-5 border border-slate-100 rounded-2xl hover:border-blue-200 hover:shadow-md transition-all bg-white relative overflow-hidden group cursor-pointer"
                                 @click="openModal(@json($apt))">
                                @if($apt['status'] == 'approved')
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500"></div>
                                @elseif($apt['status'] == 'rejected')
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-rose-500"></div>
                                @elseif($apt['status'] == 'completed')
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-slate-500"></div>
                                @else
                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-500"></div>
                                @endif

                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pl-3">
                                    <div class="flex items-start space-x-4">
                                        <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center flex-shrink-0 text-slate-500 font-bold group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                                            <i data-lucide="stethoscope" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-extrabold text-slate-900 group-hover:text-blue-600 transition-colors">Dr. {{ $apt['doctor_name'] ?? $apt['doctor']['full_name'] ?? 'Doctor' }}</h4>
                                            <div class="flex items-center text-xs font-semibold text-slate-500 mt-1 space-x-3">
                                                <span class="flex items-center"><i data-lucide="calendar" class="w-3.5 h-3.5 mr-1 text-slate-400"></i> {{ \Carbon\Carbon::parse($apt['appointment_date'])->format('d M Y') }}</span>
                                                <span class="flex items-center"><i data-lucide="clock" class="w-3.5 h-3.5 mr-1 text-slate-400"></i> {{ \Carbon\Carbon::parse($apt['appointment_date'])->format('H:i') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-lg border"
                                              :class="'{{ $apt['status'] }}' == 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ('{{ $apt['status'] }}' == 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-200' : ('{{ $apt['status'] }}' == 'completed' ? 'bg-slate-100 text-slate-700 border-slate-200' : 'bg-amber-50 text-amber-700 border-amber-200'))">
                                            {{ $apt['status'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-16 text-center">
                                <i data-lucide="calendar-off" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
                                <h5 class="text-sm font-bold text-slate-600">No appointments scheduled</h5>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal Popup -->
    @if(session('success') && session('booked_doctor'))
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md" x-data="{ showModal: true }" x-show="showModal" style="display: none;">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl w-full max-w-lg overflow-hidden transform transition-all p-6 text-center space-y-6">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-inner border border-emerald-200">
                    <i data-lucide="check-circle" class="w-10 h-10"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-900 leading-tight">Consultation Reserved Successfully</h3>
                    <p class="text-xs text-slate-500 mt-1">Your consultation quota slot has been registered in the pipeline.</p>
                </div>

                <!-- Modern Details Box -->
                <div class="bg-slate-50 border border-slate-100 p-5 rounded-2xl space-y-3 text-left">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-bold uppercase">Attending Doctor</span>
                        <span class="font-extrabold text-slate-800">{{ session('booked_doctor') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-bold uppercase">Consultation Date</span>
                        <span class="font-extrabold text-blue-600 bg-blue-50 px-2 py-1 rounded-lg border border-blue-100">{{ session('booked_date') }} @ {{ session('booked_time') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-bold uppercase">Slot Information</span>
                        <span class="font-extrabold text-emerald-600">1 General Daily Slot</span>
                    </div>
                    <div class="border-t border-slate-200/60 pt-3 flex items-start space-x-2">
                        <i data-lucide="clock" class="w-4 h-4 text-blue-600 mt-0.5"></i>
                        <p class="text-[11px] text-slate-600 leading-relaxed font-medium">
                            <span class="font-bold text-slate-800">Reminder:</span> This is not a queue number. You are welcome to visit the clinic anytime between <span class="font-bold underline text-blue-600">09:00 - 17:00</span> on that date.
                        </p>
                    </div>
                </div>

                <button @click="showModal = false" class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-xs font-black shadow-lg shadow-blue-500/25 transition-all">
                    Acknowledge & Close
                </button>
            </div>
        </div>
    @endif

    <!-- Consultation Detail Modal -->
    <div x-show="selectedAppointment" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm" style="display: none;" @click.self="closeModal()">
        <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl w-full max-w-xl overflow-hidden transform transition-all" x-transition>
            <div class="p-6 bg-slate-900 text-white flex items-center justify-between">
                <h3 class="font-black text-lg tracking-tight">Consultation Details</h3>
                <button @click="closeModal()" class="p-2 bg-slate-800 hover:bg-slate-700 rounded-full text-slate-400"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>

            <template x-if="selectedAppointment">
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-3 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Physician</span>
                            <span class="font-extrabold text-sm text-slate-800" x-text="'Dr. ' + (selectedAppointment.doctor ? selectedAppointment.doctor.full_name : 'Specialist')"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Reserved Date</span>
                            <span class="font-extrabold text-sm text-slate-800" x-text="formatModalDate(selectedAppointment.appointment_date)"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Reserved Time</span>
                            <span class="font-extrabold text-sm text-slate-800" x-text="formatModalTime(selectedAppointment.appointment_date)"></span>
                        </div>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Symptoms / Notes</span>
                        <p class="text-xs text-slate-700 bg-blue-50/30 p-3.5 rounded-xl border border-blue-100/50 font-medium leading-relaxed" x-text="selectedAppointment.notes || 'No symptoms recorded'"></p>
                    </div>
                    <div>
                        <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Diagnosis Status</span>
                        <span class="px-2 py-1 text-[9px] font-black uppercase rounded-lg border inline-block"
                              :class="selectedAppointment.status == 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (selectedAppointment.status == 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-200' : (selectedAppointment.status == 'completed' ? 'bg-slate-100 text-slate-700 border-slate-200' : (selectedAppointment.status == 'cancelled' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-amber-50 text-amber-700 border-amber-200')))"
                              x-text="selectedAppointment.status"></span>
                    </div>

                    <!-- Cancel Button -->
                    <template x-if="(selectedAppointment.status === 'pending' || selectedAppointment.status === 'approved') && !isAppointmentPast(selectedAppointment.appointment_date)">
                        <form :action="'/patient/appointments/' + selectedAppointment.id + '/cancel'" method="POST" class="mt-6 border-t pt-4 border-slate-100" @submit="if(!confirm('Are you sure you want to cancel this consultation? This action cannot be undone.')) $event.preventDefault()">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="w-full flex justify-center items-center py-2.5 px-4 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl text-xs font-bold transition-colors">
                                <i data-lucide="x-circle" class="w-4 h-4 mr-2"></i> Cancel Consultation
                            </button>
                        </form>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function patientAppointments() {
    return {
        appointments: @json($appointments ?? []),
        slotsMap: @json($slotsMap ?? []),
        selectedDoctorId: '',
        bookingDate: '',
        viewMode: 'calendar',
        isSubmitting: false,
        loadingSchedules: false,
        selectedAppointment: null,
        doctorSchedules: [],
        availableTimes: [],
        dateError: '',
        
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],

        init() {
            // Pick first doctor automatically if available
            const docSel = document.getElementById('doctor_id');
            if (docSel && docSel.options.length > 1) {
                this.selectedDoctorId = docSel.options[1].value;
            }
            this.$watch('selectedDoctorId', () => {
                this.refreshIcons();
                this.fetchDoctorSchedules();
            });
            this.$watch('viewMode', () => this.refreshIcons());
            
            // Sync calendar month/year with chosen booking date input
            this.$watch('bookingDate', (value) => {
                if (value) {
                    const parts = value.split('-');
                    if (parts.length === 3) {
                        const year = parseInt(parts[0], 10);
                        const month = parseInt(parts[1], 10) - 1; // 0-indexed month
                        if (!isNaN(year) && !isNaN(month)) {
                            this.currentYear = year;
                            this.currentMonth = month;
                        }
                    }
                }
            });
            
            if (this.selectedDoctorId) {
                this.fetchDoctorSchedules();
            }
        },

        async fetchDoctorSchedules() {
            if (!this.selectedDoctorId) return;
            this.loadingSchedules = true;
            this.availableTimes = [];
            try {
                let res = await fetch(`/patient/api/schedules/${this.selectedDoctorId}`);
                let data = await res.json();
                this.doctorSchedules = data.data || [];
                this.updateAvailableTimes();
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingSchedules = false;
            }
        },

        updateAvailableTimes() {
            this.availableTimes = [];
            this.dateError = '';
            if (!this.bookingDate || !this.doctorSchedules.length) return;
            
            let d = new Date(this.bookingDate);
            let days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            let dayName = days[d.getDay()];
            let bookingStr = this.bookingDate; // e.g. "2025-06-10"
            
            let schedule = this.doctorSchedules.find(s => {
                if (!s.is_available) return false;
                
                // If the schedule has a specific date range, check if booking date falls within it
                if (s.appointment_date) {
                    let start = s.appointment_date.substring(0, 10);
                    let end   = s.end_date ? s.end_date.substring(0, 10) : start;
                    return bookingStr >= start && bookingStr <= end;
                }
                
                // Fallback for legacy schedules without date range
                return s.day_of_week === dayName;
            });

            if (schedule) {
                let start = schedule.start_time.substring(0, 5);
                let end = schedule.end_time.substring(0, 5);
                this.availableTimes = [{
                    value: start,
                    label: `${start} - ${end}`
                }];
            } else {
                this.availableTimes = [];
                if (this.bookingDate) {
                    this.dateError = 'The selected doctor does not have available schedules for ' + (new Date(this.bookingDate).toLocaleDateString('en-GB'));
                }
            }
        },

        refreshIcons() {
            setTimeout(() => lucide.createIcons(), 60);
        },

        get blankDays() {
            let firstDay = new Date(this.currentYear, this.currentMonth, 1).getDay();
            return Array.from({length: firstDay}, (_, i) => i + 1);
        },

        get monthDays() {
            let daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
            return Array.from({length: daysInMonth}, (_, i) => i + 1);
        },

        prevMonth() {
            if (this.currentMonth == 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
            this.refreshIcons();
        },

        nextMonth() {
            if (this.currentMonth == 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
            this.refreshIcons();
        },

        getDateString(day) {
            return `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        },

        isDatePast(day) {
            let dateStr = this.getDateString(day);
            let todayStr = new Date().toISOString().split('T')[0];
            return dateStr < todayStr;
        },

        getDoctorScheduleForDate(dateStr) {
            if (!this.doctorSchedules.length) return null;
            let d = new Date(dateStr);
            let days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            let dayName = days[d.getDay()];
            
            return this.doctorSchedules.find(s => {
                if (!s.is_available) return false;
                
                if (s.appointment_date) {
                    let start = s.appointment_date.substring(0, 10);
                    let end   = s.end_date ? s.end_date.substring(0, 10) : start;
                    return dateStr >= start && dateStr <= end;
                }
                
                return s.day_of_week === dayName;
            });
        },

        isDateUnavailable(day) {
            if (this.isDatePast(day)) return true;
            if (!this.selectedDoctorId) return false;
            let dateStr = this.getDateString(day);
            return !this.getDoctorScheduleForDate(dateStr);
        },

        formatTimeRange(s) {
            if (!s) return 'Off';
            let start = s.start_time.substring(0, 5);
            let end = s.end_time.substring(0, 5);
            return `${start} - ${end}`;
        },

        getSlotsRemaining(day) {
            if (!this.selectedDoctorId) return 0;
            let dateStr = this.getDateString(day);
            let schedule = this.getDoctorScheduleForDate(dateStr);
            if (!schedule) return 0;
            
            let maxPatients = schedule.max_patients ?? 5;
            let cacheBooked = (this.slotsMap[this.selectedDoctorId] && this.slotsMap[this.selectedDoctorId][dateStr]) ? this.slotsMap[this.selectedDoctorId][dateStr] : 0;
            
            // Fallback: at minimum, it must count the patient's own bookings for that day
            let myBookings = this.appointments.filter(a => a.doctor_id == this.selectedDoctorId && a.appointment_date.startsWith(dateStr) && a.status !== 'rejected' && a.status !== 'cancelled').length;
            let booked = Math.max(cacheBooked, myBookings);
            
            return Math.max(0, maxPatients - booked);
        },

        getTodaySlotsRemaining() {
            if (!this.selectedDoctorId) return 0;
            let todayStr = new Date().toISOString().split('T')[0];
            let schedule = this.getDoctorScheduleForDate(todayStr);
            if (!schedule) return 0;
            
            let maxPatients = schedule.max_patients ?? 5;
            let cacheBooked = (this.slotsMap[this.selectedDoctorId] && this.slotsMap[this.selectedDoctorId][todayStr]) ? this.slotsMap[this.selectedDoctorId][todayStr] : 0;
            
            let myBookings = this.appointments.filter(a => a.doctor_id == this.selectedDoctorId && a.appointment_date.startsWith(todayStr) && a.status !== 'rejected' && a.status !== 'cancelled').length;
            let booked = Math.max(cacheBooked, myBookings);
            
            return Math.max(0, maxPatients - booked);
        },

        getTodayMaxSlots() {
            if (!this.selectedDoctorId) return 5;
            let todayStr = new Date().toISOString().split('T')[0];
            let schedule = this.getDoctorScheduleForDate(todayStr);
            return schedule ? (schedule.max_patients ?? 5) : 5;
        },

        isDateFull(day) {
            return this.getSlotsRemaining(day) === 0;
        },

        isDateSelected(day) {
            return this.bookingDate === this.getDateString(day);
        },

        selectDate(day) {
            if (this.isDateFull(day)) {
                alert("Quota is full for this date.");
                return;
            }
            this.bookingDate = this.getDateString(day);
            this.updateAvailableTimes();
        },

        hasOwnAppointment(day) {
            let target = this.getDateString(day);
            // Only show BOOKED indicator for the currently selected doctor
            return this.appointments.some(a =>
                a.appointment_date.startsWith(target) &&
                (!this.selectedDoctorId || String(a.doctor_id) === String(this.selectedDoctorId)) &&
                a.status !== 'rejected' && a.status !== 'cancelled'
            );
        },

        getSelectedDoctorName() {
            if (!this.selectedDoctorId) return '';
            const sel = document.getElementById('doctor_id');
            if (sel) {
                for (let opt of sel.options) {
                    if (String(opt.value) === String(this.selectedDoctorId)) return opt.text;
                }
            }
            return 'Selected Doctor';
        },

        openModal(apt) {
            this.selectedAppointment = apt;
            this.refreshIcons();
        },

        closeModal() {
            this.selectedAppointment = null;
        },

        formatModalDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
        },

        formatModalTime(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
        },

        isAppointmentPast(dateStr) {
            if (!dateStr) return true;
            let date = new Date(dateStr);
            let now = new Date();
            // Optional: allow cancel up to midnight before the appointment, or right up to it.
            // Using < now means you can cancel past the date as long as time hasn't passed.
            return date < now;
        }
    }
}
</script>
@endsection
