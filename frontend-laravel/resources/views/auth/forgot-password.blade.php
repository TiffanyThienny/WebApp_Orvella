@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div class="min-h-screen flex relative overflow-hidden bg-slate-50" x-data="{ loading: false }">

    <!-- Floating Medical Elements Background -->
    <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-400/20 blur-[100px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-cyan-400/20 blur-[120px]"></div>
        <i data-lucide="activity" class="absolute top-[20%] left-[10%] w-24 h-24 text-blue-500/10 -rotate-12"></i>
        <i data-lucide="cross" class="absolute bottom-[20%] right-[15%] w-32 h-32 text-cyan-500/10 rotate-12"></i>
    </div>

    <!-- Left Side Illustration -->
    <div class="hidden lg:flex lg:w-1/2 relative z-10 flex-col justify-center items-center p-12 bg-gradient-to-br from-blue-900 to-blue-700 text-white overflow-hidden">
        <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
        <div class="relative z-20 text-center max-w-lg">
            <div class="flex justify-center mb-8">
                <div class="w-24 h-24 bg-white/10 rounded-3xl flex items-center justify-center backdrop-blur-xl border border-white/20 shadow-2xl">
                    <i data-lucide="key-round" class="w-12 h-12 text-cyan-300"></i>
                </div>
            </div>
            <h1 class="text-5xl font-extrabold tracking-tight mb-4 text-transparent bg-clip-text bg-gradient-to-r from-white to-cyan-200">
                ORVELLA
            </h1>
            <h2 class="text-2xl font-semibold mb-6 text-blue-100">ACCOUNT RECOVERY</h2>
            <p class="text-lg text-blue-200 leading-relaxed">
                Secure account recovery for Orvella Healthcare platform. Enter your username to verify your identity and reset your password.
            </p>
        </div>

        <div class="relative z-20 mt-16 w-full max-w-md space-y-4">
            <div class="flex items-center space-x-4 bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-2xl">
                <div class="w-10 h-10 rounded-xl bg-blue-500/30 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="user-check" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-white">Step 1: Verify Identity</p>
                    <p class="text-xs text-blue-200 mt-0.5">Enter your username to verify your account</p>
                </div>
                <div class="ml-auto">
                    <div class="w-6 h-6 rounded-full {{ session('verified') ? 'bg-green-400' : 'bg-white/20' }} flex items-center justify-center">
                        <i data-lucide="check" class="w-4 h-4 text-white"></i>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-4 bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-2xl {{ session('verified') ? 'opacity-100' : 'opacity-50' }}">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/30 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="lock-keyhole" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-white">Step 2: Set New Password</p>
                    <p class="text-xs text-blue-200 mt-0.5">Create a strong new password</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 relative z-10">
        <div class="w-full max-w-md bg-white/80 backdrop-blur-xl p-10 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/50">

            <!-- Mobile Header -->
            <div class="lg:hidden text-center mb-10">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <i data-lucide="key-round" class="w-8 h-8 text-white"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900">Orvella</h1>
                <p class="text-sm text-slate-500 mt-1">Account Recovery</p>
            </div>

            <div class="hidden lg:block mb-8">
                <h2 class="text-3xl font-bold text-slate-900 mb-2">Reset Password</h2>
                <p class="text-slate-500">
                    @if(session('verified'))
                        Identity verified. Set your new password below.
                    @else
                        Enter your username to begin account recovery.
                    @endif
                </p>
            </div>

            <!-- Progress Steps -->
            <div class="flex items-center mb-8">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full {{ session('verified') ? 'bg-green-500' : 'bg-blue-600' }} flex items-center justify-center shadow-sm">
                        @if(session('verified'))
                            <i data-lucide="check" class="w-4 h-4 text-white"></i>
                        @else
                            <span class="text-xs font-bold text-white">1</span>
                        @endif
                    </div>
                    <span class="text-xs font-semibold {{ session('verified') ? 'text-green-600' : 'text-blue-600' }}">Verify</span>
                </div>
                <div class="flex-1 mx-3 h-1 rounded-full {{ session('verified') ? 'bg-blue-600' : 'bg-slate-200' }}"></div>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full {{ session('verified') ? 'bg-blue-600' : 'bg-slate-200' }} flex items-center justify-center shadow-sm">
                        <span class="text-xs font-bold {{ session('verified') ? 'text-white' : 'text-slate-400' }}">2</span>
                    </div>
                    <span class="text-xs font-semibold {{ session('verified') ? 'text-blue-600' : 'text-slate-400' }}">Reset</span>
                </div>
            </div>

            <!-- Alerts -->
            @if(session('error'))
                <div class="bg-red-50/80 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 flex items-start space-x-3 shadow-sm">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <div class="text-sm font-medium">{{ session('error') }}</div>
                </div>
            @endif

            @if(session('verified') && !session('error'))
                <div class="bg-green-50/80 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-start space-x-3 shadow-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <div class="text-sm font-medium">Username verified! Now set your new password.</div>
                </div>
            @endif

            @if(!session('verified'))
                {{-- STEP 1: Verify Username --}}
                <form class="space-y-5" action="{{ route('forgot-password.post') }}" method="POST" @submit="loading = true">
                    @csrf
                    <input type="hidden" name="step" value="verify">

                    <div class="relative group">
                        <label for="username" class="block text-sm font-semibold text-slate-700 mb-1.5 ml-1">Username <span class="text-red-500">*</span></label>
                        <div class="relative flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="user" class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                            </div>
                            <input id="username" name="username" type="text" required autocomplete="off"
                                value="{{ old('username') }}"
                                class="pl-11 pr-4 py-3.5 block w-full bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 transition-all shadow-sm"
                                placeholder="Enter your username">
                        </div>
                    </div>

                    <button type="submit" :disabled="loading"
                        class="w-full flex items-center justify-center space-x-2 py-4 px-4 btn-gradient text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 disabled:opacity-70 disabled:cursor-not-allowed">
                        <i data-lucide="search" class="w-5 h-5" x-show="!loading"></i>
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="loading" x-cloak></i>
                        <span x-text="loading ? 'Verifying...' : 'Verify Username'"></span>
                    </button>
                </form>
            @else
                {{-- STEP 2: Reset Password --}}
                <form class="space-y-5" action="{{ route('forgot-password.post') }}" method="POST" @submit="loading = true">
                    @csrf
                    <input type="hidden" name="step" value="reset">
                    <input type="hidden" name="username" value="{{ session('verified_user', old('username')) }}">

                    <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 flex items-center space-x-3">
                        <i data-lucide="user-check" class="w-5 h-5 text-blue-500 flex-shrink-0"></i>
                        <div>
                            <p class="text-xs text-blue-500 font-medium">Resetting password for</p>
                            <p class="text-sm font-bold text-blue-700">{{ session('verified_user', old('username')) }}</p>
                        </div>
                    </div>

                    <div class="relative group" x-data="{ showPw: false }">
                        <label for="new_password" class="block text-sm font-semibold text-slate-700 mb-1.5 ml-1">New Password <span class="text-red-500">*</span></label>
                        <div class="relative flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                            </div>
                            <input id="new_password" name="new_password" :type="showPw ? 'text' : 'password'" required minlength="6"
                                class="pl-11 pr-12 py-3.5 block w-full bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 transition-all shadow-sm"
                                placeholder="Min. 6 characters">
                            <button type="button" @click="showPw = !showPw" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-blue-600 transition-colors">
                                <i data-lucide="eye" class="h-5 w-5" x-show="!showPw"></i>
                                <i data-lucide="eye-off" class="h-5 w-5" x-show="showPw" x-cloak></i>
                            </button>
                        </div>
                        <p class="text-xs text-slate-400 mt-1.5 ml-1">Password must be at least 6 characters</p>
                    </div>

                    <button type="submit" :disabled="loading"
                        class="w-full flex items-center justify-center space-x-2 py-4 px-4 btn-gradient text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 disabled:opacity-70 disabled:cursor-not-allowed">
                        <i data-lucide="key-round" class="w-5 h-5" x-show="!loading"></i>
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="loading" x-cloak></i>
                        <span x-text="loading ? 'Resetting...' : 'Reset Password'"></span>
                    </button>
                </form>
            @endif

            <div class="mt-8 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center space-x-2 text-sm font-semibold text-slate-500 hover:text-blue-600 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Back to Sign In</span>
                </a>
            </div>

            <div class="mt-6 pt-6 border-t border-slate-100 flex items-center justify-center space-x-2 text-xs font-medium text-slate-400">
                <i data-lucide="shield-check" class="w-4 h-4 text-green-500"></i>
                <span>HIPAA Compliant &amp; Secure</span>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
    });
</script>
@endsection
