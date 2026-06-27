@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="min-h-screen flex relative overflow-hidden bg-slate-50" x-data="{ loading: false, showPassword: false }">
    
    <!-- Floating Medical Elements Background -->
    <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-400/20 blur-[100px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-cyan-400/20 blur-[120px]"></div>
        <i data-lucide="activity" class="absolute top-[20%] left-[10%] w-24 h-24 text-blue-500/10 -rotate-12"></i>
        <i data-lucide="heart" class="absolute bottom-[20%] right-[15%] w-32 h-32 text-cyan-500/10 rotate-12"></i>
    </div>

    <!-- Left Side Illustration (Hidden on mobile) -->
    <div class="hidden lg:flex lg:w-1/2 relative z-10 flex-col justify-center items-center p-12 bg-gradient-to-br from-blue-900 to-blue-700 text-white overflow-hidden">
        <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
        <div class="relative z-20 text-center max-w-lg">
            <div class="flex justify-center mb-8">
                <div class="w-24 h-24 bg-white/10 rounded-3xl flex items-center justify-center backdrop-blur-xl border border-white/20 shadow-2xl">
                    <i data-lucide="stethoscope" class="w-12 h-12 text-cyan-300"></i>
                </div>
            </div>
            <h1 class="text-5xl font-extrabold tracking-tight mb-4 text-transparent bg-clip-text bg-gradient-to-r from-white to-cyan-200">
                ORVELLA
            </h1>
            <h2 class="text-2xl font-semibold mb-6 text-blue-100">CLINICAL SERVICES</h2>
            <p class="text-lg text-blue-200 leading-relaxed">
                Start your premium healthcare journey. Register now to manage appointments and access clinical reports.
            </p>
        </div>
    </div>

    <!-- Right Side Register Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-8 sm:p-12 relative z-10">
        <div class="w-full max-w-md bg-white/80 backdrop-blur-xl p-10 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/50">
            
            <div class="mb-10">
                <h2 class="text-3xl font-bold text-slate-900 mb-2">Create Account</h2>
                <p class="text-slate-500">Sign up to access the healthcare portal.</p>
            </div>

            <form class="space-y-5" method="POST" action="{{ route('register') }}" @submit="loading = true">
                @csrf
                <div class="space-y-4">
                    <!-- Name -->
                    <div class="relative group">
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5 ml-1">Name <span class="text-red-500">*</span></label>
                        <div class="relative flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="user" class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                            </div>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" autofocus
                                class="pl-11 pr-4 py-3 block w-full bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 transition-all shadow-sm" 
                                placeholder="Enter your full name">
                        </div>
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="relative group">
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5 ml-1">Email Address <span class="text-red-500">*</span></label>
                        <div class="relative flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="mail" class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                            </div>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                                class="pl-11 pr-4 py-3 block w-full bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 transition-all shadow-sm" 
                                placeholder="name@example.com">
                        </div>
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="relative group">
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5 ml-1">Password <span class="text-red-500">*</span></label>
                        <div class="relative flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                            </div>
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password"
                                class="pl-11 pr-12 py-3 block w-full bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 transition-all shadow-sm" 
                                placeholder="••••••••">
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-blue-600 transition-colors">
                                <i data-lucide="eye" class="h-5 w-5" x-show="!showPassword"></i>
                                <i data-lucide="eye-off" class="h-5 w-5" x-show="showPassword" x-cloak></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="relative group">
                        <label for="password-confirm" class="block text-sm font-semibold text-slate-700 mb-1.5 ml-1">Confirm Password <span class="text-red-500">*</span></label>
                        <div class="relative flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="lock-keyhole" class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors"></i>
                            </div>
                            <input id="password-confirm" name="password_confirmation" type="password" required autocomplete="new-password"
                                class="pl-11 pr-4 py-3 block w-full bg-slate-50 border border-slate-200 rounded-xl text-slate-900 text-sm focus:bg-white focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 transition-all shadow-sm" 
                                placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loading" 
                        class="w-full flex items-center justify-center space-x-2 py-3.5 px-4 btn-gradient text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 disabled:opacity-70 disabled:cursor-not-allowed">
                        <i data-lucide="user-plus" class="w-5 h-5" x-show="!loading"></i>
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="loading" x-cloak></i>
                        <span x-text="loading ? 'Creating Account...' : 'Register'"></span>
                    </button>
                </div>

                <p class="text-center text-xs text-slate-500 mt-6">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-800 transition-colors">Sign In</a>
                </p>
            </form>
            
            <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-center space-x-2 text-[10px] font-medium text-slate-400">
                <i data-lucide="shield-check" class="w-4 h-4 text-green-500"></i>
                <span>HIPAA Compliant & Secure</span>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endsection

