@props(['users', 'role', 'roleId', 'doctors' => []])

@php
    $roleLabel = $role;
    $roleLower = strtolower($role);
    $isPatient = ($roleId == 3);
    $isDoctor = ($roleId == 2);
    $hasErrors = $errors->any();
    $todayDate  = now()->format('Y-m-d');
@endphp

<div class="space-y-6">
    <!-- Flash Messages -->
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl flex items-center space-x-3" id="flash-success">
        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-xl flex items-center space-x-3" id="flash-error">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span class="font-medium">{{ session('error') }}</span>
    </div>
    @endif

    <!-- Header + Add Button -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-800">{{ $roleLabel }} Management</h3>
                <p class="text-sm text-slate-500 mt-1">{{ count($users) }} {{ strtolower($roleLabel) }}{{ count($users) !== 1 ? 's' : '' }} registered</p>
            </div>
            <button onclick="openAddModal()" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg shadow-blue-500/20 flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                <span>Add {{ $roleLabel }}</span>
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4 font-semibold">User</th>
                        <th class="px-6 py-4 font-semibold">Username</th>
                        <th class="px-6 py-4 font-semibold">Email</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold">Joined</th>
                        <th class="px-6 py-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($users as $u)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md">
                                    {{ strtoupper(substr($u['full_name'] ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $u['full_name'] ?? '-' }}</p>
                                    <p class="text-xs text-slate-400">{{ $u['phone'] ?? 'No phone' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-600 font-mono text-xs">{{ $u['username'] ?? '-' }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $u['email'] ?? '-' }}</td>
                        <td class="px-6 py-4">
                            @if($u['is_profile_complete'] ?? false)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span>Complete
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full mr-1.5"></span>Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs">{{ isset($u['created_at']) ? \Carbon\Carbon::parse($u['created_at'])->format('d M Y') : '-' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end space-x-2">
                                <button onclick="openEditModal({{ json_encode($u) }})" class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-semibold transition-colors">Edit</button>
                                <button onclick="openDeleteModal({{ $u['id'] }}, '{{ addslashes($u['full_name'] ?? '') }}')" class="px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-semibold transition-colors">Delete</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                <p class="font-medium">No {{ strtolower($roleLabel) }}s found</p>
                                <p class="text-sm mt-1">Click "Add {{ $roleLabel }}" to create one.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══════════ ADD MODAL ═══════════ -->
<div id="addModal" class="{{ $hasErrors ? '' : 'hidden' }} fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 max-h-[90vh] overflow-y-auto" id="addModalBox">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
            <h3 class="text-lg font-bold text-slate-800">Add New {{ $roleLabel }}</h3>
            <button onclick="tryCloseAddModal()" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        {{-- Server-side errors shown inside modal --}}
        @if($errors->any())
        <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl" id="serverErrors">
            <div class="flex items-center space-x-2 mb-1">
                <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="font-semibold text-sm">Please correct the following errors:</span>
            </div>
            <ul class="list-disc list-inside text-sm space-y-0.5 ml-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form id="addForm" action="{{ route('admin.users.create') }}" method="POST" class="p-6 space-y-4" novalidate onsubmit="return validateAddForm(event)">
            @csrf
            <input type="hidden" name="role_id" value="{{ $roleId }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" id="add_full_name" value="{{ old('full_name') }}"
                        required minlength="2" maxlength="100"
                        oninput="validateField(this, 'Full name must be at least 2 characters.', v => v.trim().length >= 2)"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        placeholder="Full name">
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="add_username" value="{{ old('username') }}"
                        required minlength="3" maxlength="50"
                        oninput="validateField(this, 'Username must be at least 3 characters, alphanumeric or ._@ only.', v => /^[a-zA-Z0-9._@]{3,}$/.test(v.trim()))"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        placeholder="username">
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="add_email" value="{{ old('email') }}"
                        required maxlength="100"
                        oninput="validateField(this, 'Please enter a valid email address.', v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()))"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        placeholder="email@example.com">
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" name="password" id="add_password"
                            required minlength="6"
                            oninput="validateField(this, 'Password must be at least 6 characters.', v => v.length >= 6)"
                            class="field-input w-full border border-slate-300 rounded-xl pl-4 pr-10 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                            placeholder="Min. 6 characters">
                        <button type="button" onclick="togglePasswordVisibility('add_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-650 focus:outline-none">
                            <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg class="w-5 h-5 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                        </button>
                    </div>
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" id="add_phone" value="{{ old('phone') }}"
                        maxlength="20"
                        oninput="validateField(this, 'Phone must be digits only, min 12 digits.', v => v === '' || /^[0-9]{12,}$/.test(v.trim()))"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        placeholder="Phone number">
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Address</label>
                    <input type="text" name="address" id="add_address" value="{{ old('address') }}"
                        maxlength="255"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        placeholder="Full address">
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
            </div>

            @if($isDoctor)
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Specialty <span class="text-red-500">*</span></label>
                <input type="text" name="specialty" id="add_specialty" value="{{ old('specialty') }}"
                    required maxlength="100"
                    oninput="validateField(this, 'Specialty is required.', v => v.trim().length > 0)"
                    class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                    placeholder="e.g. General Practitioner, Pediatrician">
                <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
            </div>
            @endif

            @if($isPatient)
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Date of Birth <span class="text-red-500">*</span></label>
                    <input type="date" name="date_of_birth" id="add_dob" value="{{ old('date_of_birth') }}"
                        required max="{{ $todayDate }}"
                        oninput="validateField(this, 'Date of birth cannot be today or in the future.', v => v !== '' && v < '{{ $todayDate }}')"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Gender <span class="text-red-500">*</span></label>
                    <select name="gender" id="add_gender" required
                        onchange="validateField(this, 'Select gender.', v => v !== '')"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all bg-white">
                        <option value="">-- Select --</option>
                        <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Emergency Contact <span class="text-red-500">*</span></label>
                <input type="text" name="emergency_contact" id="add_emergency" value="{{ old('emergency_contact') }}"
                    required maxlength="20"
                    oninput="validateField(this, 'Emergency contact must be digits only, min 8 digits.', v => /^[0-9]{8,}$/.test(v.trim()))"
                    class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                    placeholder="Emergency contact number">
                <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Allergies</label>
                <textarea name="allergies" rows="2" maxlength="500"
                    class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all resize-none"
                    placeholder="e.g., Penicillin, Peanuts, Seafood">{{ old('allergies') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Medical History</label>
                <textarea name="medical_history" rows="2" maxlength="500"
                    class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all resize-none"
                    placeholder="e.g., Diabetes, Hypertension, Asthma">{{ old('medical_history') }}</textarea>
            </div>
            @endif

            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="tryCloseAddModal()" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                <button type="submit" id="addSubmitBtn" class="px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-xl shadow-lg shadow-blue-500/20 transition-all flex items-center space-x-2">
                    <span id="addSubmitText">Create {{ $roleLabel }}</span>
                    <svg id="addSpinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════ EDIT MODAL ═══════════ -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('editModal').classList.add('hidden')"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">Edit {{ $roleLabel }}</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="editForm" method="POST" class="p-6 space-y-4" novalidate onsubmit="return validateEditForm(event)">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="full_name" id="edit_full_name" required minlength="2"
                    oninput="validateField(this, 'Full name must be at least 2 characters.', v => v.trim().length >= 2)"
                    class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="edit_username" required minlength="3" maxlength="50"
                        oninput="validateField(this, 'Username must be at least 3 characters, alphanumeric or ._@ only.', v => /^[a-zA-Z0-9._@]{3,}$/.test(v.trim()))"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        placeholder="username">
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="edit_email" required maxlength="100"
                        oninput="validateField(this, 'Please enter a valid email address.', v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()))"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password <span class="text-slate-400 font-normal">(Leave blank if unchanged)</span></label>
                <div class="relative">
                    <input type="password" name="password" id="edit_password" minlength="6"
                        oninput="validateField(this, 'Password must be at least 6 characters if filled.', v => v === '' || v.length >= 6)"
                        class="field-input w-full border border-slate-300 rounded-xl pl-4 pr-10 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePasswordVisibility('edit_password', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-650 focus:outline-none">
                        <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <svg class="w-5 h-5 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                    </button>
                </div>
                <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Phone</label>
                    <input type="text" name="phone" id="edit_phone"
                        oninput="validateField(this, 'Phone must be digits only, min 12 digits.', v => v === '' || /^[0-9]{12,}$/.test(v.trim()))"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Address</label>
                    <input type="text" name="address" id="edit_address"
                        class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
                </div>
            </div>
            @if($isDoctor)
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Specialty <span class="text-red-500">*</span></label>
                <input type="text" name="specialty" id="edit_specialty" required maxlength="100"
                    oninput="validateField(this, 'Specialty is required.', v => v.trim().length > 0)"
                    class="field-input w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                <p class="field-error text-xs text-red-500 mt-1 hidden"></p>
            </div>
            @endif
            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 rounded-xl shadow-lg shadow-blue-500/20 transition-all">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════ DELETE MODAL ═══════════ -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('deleteModal').classList.add('hidden')"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10">
        <div class="p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Delete User</h3>
            <p class="text-sm text-slate-500 mb-6">Are you sure you want to delete <span id="deleteUserName" class="font-semibold text-slate-800"></span>? This action cannot be undone.</p>
            <form id="deleteForm" method="POST" class="flex justify-center space-x-3">
                @csrf
                @method('DELETE')
                <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden')" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 rounded-xl shadow-lg shadow-red-500/20 transition-all">Delete</button>
            </form>
        </div>
    </div>
</div>

<style>
.field-input.is-invalid { border-color: #f87171; background-color: #fff5f5; }
.field-input.is-valid   { border-color: #34d399; }
</style>

<script>
// ─── Helpers ────────────────────────────────────────────────
function validateField(input, errorMsg, testFn) {
    const errEl = input.parentElement.querySelector('.field-error');
    const val = input.value;
    const ok = testFn(val);
    if (!ok) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        if (errEl) { errEl.textContent = errorMsg; errEl.classList.remove('hidden'); }
    } else {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        if (errEl) { errEl.classList.add('hidden'); }
    }
    return ok;
}

function triggerAllValidations(formId) {
    const form = document.getElementById(formId);
    let allOk = true;
    form.querySelectorAll('[oninput]').forEach(input => {
        input.dispatchEvent(new Event('input'));
        if (input.classList.contains('is-invalid')) allOk = false;
    });
    form.querySelectorAll('[onchange]').forEach(sel => {
        sel.dispatchEvent(new Event('change'));
        if (sel.classList.contains('is-invalid')) allOk = false;
    });
    // Check required fields that have no oninput (plain required)
    form.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            const errEl = input.parentElement.querySelector('.field-error');
            if (errEl) { errEl.textContent = 'This field is required.'; errEl.classList.remove('hidden'); }
            allOk = false;
        }
    });
    // Scroll to first invalid
    const firstInvalid = form.querySelector('.is-invalid');
    if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return allOk;
}

// ─── Add Modal ──────────────────────────────────────────────
function openAddModal() {
    const form = document.getElementById('addForm');
    if (form) {
        form.reset();
        form.querySelectorAll('.field-input').forEach(el => {
            el.classList.remove('is-invalid','is-valid');
        });
        form.querySelectorAll('.field-error').forEach(el => el.classList.add('hidden'));
    }
    document.getElementById('addModal').classList.remove('hidden');
}

function tryCloseAddModal() {
    // If there are server-side errors shown, warn the user
    const modal = document.getElementById('addModal');
    const hasInvalid = document.getElementById('addForm').querySelectorAll('.is-invalid').length > 0;
    // Always allow cancel (user choice)
    modal.classList.add('hidden');
}

function validateAddForm(e) {
    const ok = triggerAllValidations('addForm');
    if (!ok) {
        e.preventDefault();
        return false;
    }
    // Show spinner
    document.getElementById('addSubmitText').textContent = 'Saving...';
    document.getElementById('addSpinner').classList.remove('hidden');
    document.getElementById('addSubmitBtn').disabled = true;
    return true;
}

// ─── Edit Modal ─────────────────────────────────────────────
function openEditModal(user) {
    document.getElementById('editForm').action = '/admin/users/' + user.id;
    document.getElementById('edit_full_name').value = user.full_name || '';
    document.getElementById('edit_username').value = user.username || '';
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_password').value = ''; // Always clear password field
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_address').value = user.address || '';
    // Specialty field only present for Doctors
    const specialtyEl = document.getElementById('edit_specialty');
    if (specialtyEl) specialtyEl.value = user.specialty || '';
    document.getElementById('editModal').querySelectorAll('.field-input').forEach(el => {
        el.classList.remove('is-invalid','is-valid');
    });
    document.getElementById('editModal').querySelectorAll('.field-error').forEach(el => el.classList.add('hidden'));
    document.getElementById('editModal').classList.remove('hidden');
}

function validateEditForm(e) {
    const ok = triggerAllValidations('editForm');
    if (!ok) {
        e.preventDefault();
        return false;
    }
    return true;
}

// ─── Delete Modal ───────────────────────────────────────────
function openDeleteModal(id, name) {
    document.getElementById('deleteForm').action = '/admin/users/' + id;
    document.getElementById('deleteUserName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
}

// Auto-dismiss flash messages
setTimeout(() => {
    ['flash-success','flash-error'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.transition = 'opacity 0.5s', el.style.opacity = '0', setTimeout(() => el.remove(), 500);
    });
}, 4000);

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const eyeIcon = btn.querySelector('.eye-icon');
    const eyeOffIcon = btn.querySelector('.eye-off-icon');
    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeOffIcon.classList.remove('hidden');
    } else {
        input.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeOffIcon.classList.add('hidden');
    }
}
</script>
