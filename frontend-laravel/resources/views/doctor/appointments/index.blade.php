@extends('layouts.dashboard')

@section('title', 'Clinical Appointments Workspace')

@section('dashboard_content')
<div class="max-w-7xl mx-auto space-y-6" x-data="appointmentCalendar()">
    
    <!-- Top Stats Banner -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-96 h-96 bg-gradient-to-bl from-blue-50/50 via-transparent to-transparent rounded-bl-full pointer-events-none opacity-60"></div>
        <div class="relative z-10 space-y-1">
            <h2 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                <i data-lucide="calendar" class="text-blue-600"></i>
                <span>Clinical Schedule Workspace</span>
            </h2>
            <p class="text-slate-500 text-xs max-w-xl">
                Track all daily reserved slot quotas, evaluate reported patient symptoms, and update appointment records.
            </p>
        </div>
        <div class="flex items-center space-x-2 relative z-10">
            <div class="flex bg-slate-100 p-1 rounded-2xl border border-slate-200">
                <button @click="viewMode = 'calendar'" :class="viewMode == 'calendar' ? 'bg-white shadow-sm text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-800'" class="px-4 py-2 rounded-xl text-xs transition-all flex items-center space-x-1.5">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    <span>Calendar Grid</span>
                </button>
                <button @click="viewMode = 'list'" :class="viewMode == 'list' ? 'bg-white shadow-sm text-blue-600 font-bold' : 'text-slate-600 hover:text-slate-800'" class="px-4 py-2 rounded-xl text-xs transition-all flex items-center space-x-1.5">
                    <i data-lucide="list-filter" class="w-3.5 h-3.5"></i>
                    <span>List Register</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Notifications / Toast alerts -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl shadow-sm flex items-start space-x-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 mt-0.5"></i>
            <div class="flex-1 text-xs font-semibold text-emerald-800">{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 p-4 rounded-2xl shadow-sm flex items-start space-x-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 mt-0.5"></i>
            <div class="flex-1 text-xs font-semibold text-red-850">{{ session('error') }}</div>
        </div>
    @endif

    <!-- Split Workspace Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- LEFT: Calendar Grid or Full List Register (8 columns) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Calendar Grid Card -->
            <div x-show="viewMode == 'calendar'" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <!-- Navigation -->
                <div class="p-5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button @click="prevMonth()" class="p-2 rounded-xl bg-white border border-slate-200 hover:border-slate-350 hover:bg-slate-50 transition-colors shadow-sm">
                            <i data-lucide="chevron-left" class="w-4 h-4 text-slate-600"></i>
                        </button>
                        <h3 class="text-sm font-black text-slate-800 w-44 text-center uppercase tracking-wider" x-text="monthNames[month] + ' ' + year"></h3>
                        <button @click="nextMonth()" class="p-2 rounded-xl bg-white border border-slate-200 hover:border-slate-350 hover:bg-slate-50 transition-colors shadow-sm">
                            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-600"></i>
                        </button>
                    </div>
                    <div class="flex items-center space-x-4 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5 shadow-sm shadow-emerald-500/20"></span> Completed/Approved</span>
                        <span class="flex items-center"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-1.5 shadow-sm shadow-amber-500/20"></span> Pending</span>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Day Labels -->
                    <div class="grid grid-cols-7 gap-3 mb-3 text-center">
                        <template x-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day">
                            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest py-1" x-text="day"></div>
                        </template>
                    </div>

                    <!-- Date Blocks -->
                    <div class="grid grid-cols-7 gap-3">
                        <template x-for="blank in blankDays" :key="'blank-' + blank">
                            <div class="bg-slate-50/30 rounded-2xl border border-slate-100/50 opacity-40 min-h-[90px] pointer-events-none"></div>
                        </template>

                        <template x-for="date in noOfDays" :key="'date-' + date">
                            <div class="bg-white rounded-2xl border min-h-[95px] p-2 flex flex-col justify-between relative transition-all duration-200 cursor-pointer"
                                 :class="[
                                     isToday(date) ? 'border-blue-500 bg-blue-50/10 shadow-sm' : 'border-slate-100 hover:border-slate-300 hover:shadow-md',
                                     selectedDate === date ? 'border-blue-600 ring-2 ring-blue-600/20' : ''
                                 ]"
                                 @click="selectDate(date)">
                                
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold" :class="isToday(date) ? 'text-blue-600 font-extrabold' : 'text-slate-600'" x-text="date"></span>
                                    
                                    <!-- Indicator badge with counts -->
                                    <template x-if="getDayAppointments(date).length > 0">
                                        <span class="text-[9px] font-black px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-700" x-text="getDayAppointments(date).length"></span>
                                    </template>
                                </div>

                                <!-- Dynamic event summary bubble inside date grid -->
                                <div class="flex-1 overflow-hidden mt-1.5 space-y-1">
                                    <template x-for="apt in getDayAppointments(date).slice(0, 2)" :key="apt.id">
                                        <div class="text-[8px] font-extrabold px-1.5 py-0.5 rounded-md truncate border block"
                                             :class="apt.status === 'completed' || apt.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : (apt.status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-amber-50 text-amber-700 border-amber-100')">
                                            <span x-text="apt.patient_name || 'Patient'"></span>
                                        </div>
                                    </template>
                                    <template x-if="getDayAppointments(date).length > 2">
                                        <span class="text-[7px] text-slate-400 font-bold block pl-1" x-text="'+' + (getDayAppointments(date).length - 2) + ' more'"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- List View Register -->
            <div x-show="viewMode == 'list'" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-slate-800 text-base">Schedule List Directory</h3>
                        <p class="text-xs text-slate-500 font-medium">Browse and search through all registered consultations.</p>
                    </div>
                </div>

                <!-- Filter & Search Sub-bar -->
                <div class="px-6 py-3 bg-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Tabs -->
                    <div class="flex bg-slate-100 p-1 rounded-2xl w-full sm:w-auto overflow-x-auto custom-scrollbar">
                        <button @click="listTab = 'now'; refreshIcons();" :class="listTab === 'now' ? 'bg-white text-blue-600 shadow-sm font-bold' : 'text-slate-655 hover:text-slate-900'" class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-2 whitespace-nowrap">
                            Now
                        </button>
                        <button @click="listTab = 'upcoming'; refreshIcons();" :class="listTab === 'upcoming' ? 'bg-white text-blue-600 shadow-sm font-bold' : 'text-slate-655 hover:text-slate-900'" class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-2 whitespace-nowrap">
                            Upcoming
                        </button>
                        <button @click="listTab = 'past'; refreshIcons();" :class="listTab === 'past' ? 'bg-white text-blue-600 shadow-sm font-bold' : 'text-slate-655 hover:text-slate-900'" class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-2 whitespace-nowrap">
                            Past
                        </button>
                        <button @click="listTab = 'done'; refreshIcons();" :class="listTab === 'done' ? 'bg-white text-blue-600 shadow-sm font-bold' : 'text-slate-655 hover:text-slate-900'" class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-center space-x-2 whitespace-nowrap">
                            Done
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-slate-400"></i>
                        <input type="text" x-model="searchQuery" placeholder="Search patient name..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition-all placeholder:text-slate-450">
                    </div>
                </div>

                <div class="p-6 divide-y divide-slate-100">
                    <template x-for="apt in filteredAppointments()" :key="'list-apt-' + apt.id">
                        <div class="py-5 flex items-center justify-between gap-6 hover:bg-slate-50 p-4 rounded-2xl transition-colors cursor-pointer"
                             @click="selectAppointmentDetails(apt)">
                            <div class="flex items-start space-x-4">
                                <div class="w-11 h-11 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-inner">
                                    <i data-lucide="user" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-extrabold text-slate-800" x-text="apt.patient_name || 'Unknown Patient'"></h4>
                                    <div class="flex items-center text-xs font-semibold text-slate-400 space-x-4 mt-1">
                                        <span class="flex items-center"><i data-lucide="calendar" class="w-3.5 h-3.5 mr-1 text-slate-300"></i> <span x-text="formatDate(apt.appointment_date)"></span></span>
                                        <span class="flex items-center"><i data-lucide="clock" class="w-3.5 h-3.5 mr-1 text-slate-300"></i> 09:00 - 17:00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border tracking-wider"
                                      :class="apt.status === 'completed' || apt.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (apt.status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200')"
                                      x-text="apt.status"></span>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400"></i>
                            </div>
                        </div>
                    </template>

                    <template x-if="filteredAppointments().length === 0">
                        <div class="py-16 text-center">
                            <i data-lucide="calendar-off" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
                            <h5 class="text-sm font-bold text-slate-500">No scheduled consultations found.</h5>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- RIGHT: Interactive Detail & Actions Workspace Panel (4 columns) -->
        <div class="lg:col-span-4">
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden sticky top-24 space-y-6 p-6">
                
                <!-- Workspace State 1: No Date Selected -->
                <div x-show="selectedDate === null && selectedAppointment === null" class="py-16 text-center space-y-3">
                    <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto shadow-inner border border-blue-100/50 animate-pulse">
                        <i data-lucide="mouse-pointer-click" class="w-8 h-8"></i>
                    </div>
                    <h4 class="text-sm font-extrabold text-slate-700">Select Date on Calendar</h4>
                    <p class="text-xs text-slate-450 max-w-[220px] mx-auto leading-relaxed">Click any highlighted date inside the grid to manage daily slot counts and patient symptoms detail.</p>
                </div>

                <!-- Workspace State 2: Date is Selected -->
                <div x-show="selectedDate !== null" class="space-y-6">
                    
                    <!-- Date slot occupancy summary box -->
                    <div class="bg-slate-50 border border-slate-100 p-5 rounded-2xl space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-slate-500 uppercase tracking-wider">Date Picked</span>
                            <span class="text-xs font-black text-slate-800" x-text="monthNames[month] + ' ' + selectedDate + ', ' + year"></span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 border-t border-slate-200/50 pt-3">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Slots Booked</span>
                                <span class="text-lg font-black text-slate-800" x-text="getDayAppointments(selectedDate).length + ' / ' + getMaxPatients(selectedDate)"></span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Remaining Slots</span>
                                <span class="text-lg font-black" :class="Math.max(0, getMaxPatients(selectedDate) - getDayAppointments(selectedDate).length) === 0 ? 'text-rose-600' : 'text-emerald-600'" x-text="Math.max(0, getMaxPatients(selectedDate) - getDayAppointments(selectedDate).length)"></span>
                            </div>
                        </div>

                        <!-- Slots warning color line -->
                        <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden mt-2">
                            <div class="h-full transition-all duration-300"
                                 :class="getDayAppointments(selectedDate).length >= getMaxPatients(selectedDate) ? 'bg-rose-500' : ((getDayAppointments(selectedDate).length / getMaxPatients(selectedDate)) >= 0.6 ? 'bg-amber-500' : 'bg-emerald-500')"
                                 :style="'width: ' + ((getDayAppointments(selectedDate).length / getMaxPatients(selectedDate)) * 100) + '%'">
                            </div>
                        </div>
                    </div>

                    <!-- Patient consult list on the selected date -->
                    <div class="space-y-3">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center"><i data-lucide="users" class="w-3.5 h-3.5 mr-1.5 text-blue-600"></i> Daily Patient Queue</h4>
                        <div class="space-y-2 max-h-[180px] overflow-y-auto custom-scrollbar">
                            <template x-for="apt in getDayAppointments(selectedDate)" :key="'day-apt-' + apt.id">
                                <div class="p-3.5 bg-white border border-slate-100 rounded-2xl hover:border-blue-300 cursor-pointer transition-all flex items-center justify-between"
                                     :class="selectedAppointment && selectedAppointment.id === apt.id ? 'border-blue-500 bg-blue-50/20 ring-1 ring-blue-500/20' : ''"
                                     @click="selectedAppointment = apt">
                                    <div class="overflow-hidden">
                                        <p class="text-xs font-bold text-slate-800 truncate" x-text="apt.patient_name || 'Patient'"></p>
                                        <p class="text-[10px] font-medium text-slate-400 mt-0.5">Consultation Slot</p>
                                    </div>
                                    <span class="px-2 py-0.5 text-[8px] font-black uppercase rounded border tracking-wider"
                                          :class="apt.status === 'completed' || apt.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : (apt.status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-amber-50 text-amber-700 border-amber-100')"
                                          x-text="apt.status"></span>
                                </div>
                            </template>

                            <template x-if="getDayAppointments(selectedDate).length === 0">
                                <div class="p-6 bg-slate-50 border border-slate-100/50 rounded-2xl text-center">
                                    <p class="text-xs text-slate-400 font-bold">No consultations scheduled for this date.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Selected Appointment Review Sheet -->
                <div x-show="selectedAppointment !== null" class="border-t border-slate-100 pt-6 space-y-5">
                    <h4 class="text-[10px] font-black text-slate-450 uppercase tracking-widest flex items-center"><i data-lucide="file-text" class="w-3.5 h-3.5 mr-1.5 text-blue-600"></i> Consultation Review Sheet</h4>
                    
                    <div class="space-y-4">
                        <!-- Patient Card -->
                        <div class="flex items-center space-x-3.5">
                            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center font-bold text-sm shadow-inner flex-shrink-0 border border-blue-200">
                                <span x-text="selectedAppointment ? (selectedAppointment.patient_name || 'U').substring(0, 1) : 'U'"></span>
                            </div>
                            <div class="overflow-hidden">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Consulting Patient</span>
                                <h4 class="text-sm font-black text-slate-800 truncate" x-text="selectedAppointment ? selectedAppointment.patient_name : 'Patient'"></h4>
                            </div>
                        </div>

                        <!-- Symptoms Notes -->
                        <div class="space-y-1">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Reported Symptoms</span>
                            <div class="bg-blue-50/30 border border-blue-100/50 p-3 rounded-2xl text-xs text-slate-700 leading-relaxed font-medium" x-text="selectedAppointment ? selectedAppointment.notes : 'No symptoms registered.'"></div>
                        </div>

                        <!-- Booking Status Details -->
                        <div class="flex justify-between items-center text-xs border-b border-slate-100 pb-2">
                            <span class="text-slate-400 font-bold">Registration Status</span>
                            <span class="px-2 py-0.5 text-[8px] font-black uppercase rounded border"
                                  :class="selectedAppointment && selectedAppointment.status === 'completed' || selectedAppointment && selectedAppointment.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : (selectedAppointment && selectedAppointment.status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-amber-50 text-amber-700 border-amber-100')"
                                  x-text="selectedAppointment ? selectedAppointment.status : 'pending'"></span>
                        </div>

                        <!-- Direct quick actions forms -->
                        <div class="space-y-2 pt-2">
                            <!-- Pending state actions -->
                            <div x-show="selectedAppointment && selectedAppointment.status === 'pending'" class="grid grid-cols-2 gap-3">
                                <form :action="'/doctor/appointments/' + (selectedAppointment ? selectedAppointment.id : 0) + '/status'" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider shadow-md hover:shadow-emerald-500/20 transition-all flex items-center justify-center gap-1.5" @click="if(!confirm('Approve this appointment?')) event.preventDefault()">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Approve
                                    </button>
                                </form>
                                <form :action="'/doctor/appointments/' + (selectedAppointment ? selectedAppointment.id : 0) + '/status'" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider shadow-md hover:shadow-rose-500/20 transition-all flex items-center justify-center gap-1.5" @click="if(!confirm('Reject this appointment?')) event.preventDefault()">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i> Reject
                                    </button>
                                </form>
                            </div>

                            <!-- Approved state actions -->
                            <div x-show="selectedAppointment && selectedAppointment.status === 'approved'" class="space-y-2">
                                <form :action="'/doctor/appointments/' + (selectedAppointment ? selectedAppointment.id : 0) + '/status'" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider shadow-lg hover:shadow-blue-500/20 transition-all flex items-center justify-center gap-1.5">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Mark As Completed
                                    </button>
                                </form>
                            </div>

                            <!-- Completed state actions -->
                            <div x-show="selectedAppointment && selectedAppointment.status === 'completed'" class="space-y-2">
                                <a :href="'/doctor/patients/' + (selectedAppointment ? selectedAppointment.patient_id : 0)" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider shadow-lg hover:shadow-emerald-500/20 transition-all flex items-center justify-center gap-1.5">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> View Results & Patient Portfolio
                                </a>
                            </div>

                            <!-- Link to patient detail and history -->
                            <div class="grid grid-cols-2 gap-3" x-show="selectedAppointment">
                                <a :href="'/doctor/patients/' + (selectedAppointment ? selectedAppointment.patient_id : 0)" class="w-full py-2.5 border border-slate-200 hover:border-slate-350 hover:bg-slate-50 rounded-xl text-[9px] font-extrabold uppercase tracking-wider text-slate-600 transition-all flex items-center justify-center gap-1">
                                    <i data-lucide="user" class="w-3 h-3 text-slate-400"></i> Open Profile
                                </a>
                                <a :href="'/doctor/patients/' + (selectedAppointment ? selectedAppointment.patient_id : 0) + '#history'" class="w-full py-2.5 border border-slate-200 hover:border-slate-350 hover:bg-slate-50 rounded-xl text-[9px] font-extrabold uppercase tracking-wider text-slate-600 transition-all flex items-center justify-center gap-1">
                                    <i data-lucide="history" class="w-3 h-3 text-slate-400"></i> History Log
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function appointmentCalendar() {
    return {
        viewMode: 'calendar',
        month: new Date().getMonth(),
        year: new Date().getFullYear(),
        selectedDate: null,
        selectedAppointment: null,
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        blankDays: [],
        noOfDays: [],
        appointments: @json($appointments ?? []),
        schedules: @json($schedules ?? []),
        searchQuery: '',
        listTab: 'now',

        init() {
            this.getNoOfDays();
            // Automatically select today if there are appointments or highlight today
            const today = new Date();
            if (this.month === today.getMonth() && this.year === today.getFullYear()) {
                this.selectDate(today.getDate());
            }
        },

        refreshIcons() {
            setTimeout(() => lucide.createIcons(), 50);
        },

        isToday(date) {
            const today = new Date();
            return date === today.getDate() && this.month === today.getMonth() && this.year === today.getFullYear();
        },

        getNoOfDays() {
            let daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
            let dayOfWeek = new Date(this.year, this.month, 1).getDay();
            let blankdaysArray = [];
            for (let i = 1; i <= dayOfWeek; i++) {
                blankdaysArray.push(i);
            }
            let daysArray = [];
            for (let i = 1; i <= daysInMonth; i++) {
                daysArray.push(i);
            }
            this.blankDays = blankdaysArray;
            this.noOfDays = daysArray;
        },

        prevMonth() {
            if (this.month == 0) {
                this.month = 11;
                this.year--;
            } else {
                this.month--;
            }
            this.selectedDate = null;
            this.selectedAppointment = null;
            this.getNoOfDays();
            this.refreshIcons();
        },

        nextMonth() {
            if (this.month == 11) {
                this.month = 0;
                this.year++;
            } else {
                this.month++;
            }
            this.selectedDate = null;
            this.selectedAppointment = null;
            this.getNoOfDays();
            this.refreshIcons();
        },

        selectDate(date) {
            this.selectedDate = date;
            const dayApts = this.getDayAppointments(date);
            if (dayApts.length > 0) {
                this.selectedAppointment = dayApts[0];
            } else {
                this.selectedAppointment = null;
            }
            this.refreshIcons();
        },

        selectAppointmentDetails(apt) {
            const dateObj = new Date(apt.appointment_date);
            this.month = dateObj.getMonth();
            this.year = dateObj.getFullYear();
            this.selectedDate = dateObj.getDate();
            this.selectedAppointment = apt;
            this.viewMode = 'calendar';
            this.getNoOfDays();
            this.refreshIcons();
        },

        getDayAppointments(date) {
            return this.appointments.filter(apt => {
                const aptDate = new Date(apt.appointment_date);
                return aptDate.getDate() === date && aptDate.getMonth() === this.month && aptDate.getFullYear() === this.year;
            });
        },

        filteredAppointments() {
            const today = new Date();
            today.setHours(0,0,0,0);
            
            return this.appointments.filter(apt => {
                const aptDate = new Date(apt.appointment_date);
                aptDate.setHours(0,0,0,0);
                
                // Match Search query
                if (this.searchQuery.trim() !== '') {
                    const query = this.searchQuery.toLowerCase();
                    const pName = (apt.patient_name || '').toLowerCase();
                    if (!pName.includes(query)) return false;
                }

                const isToday = aptDate.getTime() === today.getTime();
                const isUpcoming = aptDate.getTime() > today.getTime();
                const isPast = aptDate.getTime() < today.getTime();
                
                if (this.listTab === 'now') {
                    return isToday && (apt.status === 'pending' || apt.status === 'approved');
                } else if (this.listTab === 'upcoming') {
                    return isUpcoming && (apt.status === 'pending' || apt.status === 'approved');
                } else if (this.listTab === 'past') {
                    return isPast && apt.status !== 'completed';
                } else if (this.listTab === 'done') {
                    return apt.status === 'completed';
                }
                return true;
            });
        },

        getMaxPatients(date) {
            let m = this.month + 1;
            let d = date;
            let dateStr = `${this.year}-${m.toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}`;
            
            let dateObj = new Date(this.year, this.month, date);
            let days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            let dayName = days[dateObj.getDay()];

            let maxSlots = 5;
            for (let s of this.schedules) {
                if (s.is_available) {
                    if (s.appointment_date) {
                        let start = s.appointment_date.substring(0, 10);
                        let end = s.end_date ? s.end_date.substring(0, 10) : start;
                        if (dateStr >= start && dateStr <= end) {
                            maxSlots = s.max_patients;
                            break;
                        }
                    } else if (s.day_of_week === dayName) {
                        maxSlots = s.max_patients;
                        break;
                    }
                }
            }
            return maxSlots;
        },

        formatDate(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        }
    }
}
</script>
@endsection
