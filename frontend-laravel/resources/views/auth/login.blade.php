@extends('layouts.app')

@section('title', 'Sign In — Orvella')

@section('content')
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    -webkit-font-smoothing: antialiased;
    background-color: #F3F4F6 !important;
    color: #1F2937;
    overflow-x: hidden;
}

body > #app {
    min-height: 100vh;
    display: flex;
    align-items: stretch;
}
body > #app > main { display: contents; }

/* ── Cozy Light Background with Radial Ambient Glows ── */
.login-wrapper {
    position: relative;
    width: 100%;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 24px;
    background: radial-gradient(circle at 10% 20%, #F9FAFB 0%, #E5E7EB 100%);
    overflow: hidden;
}

/* Ambient glow blobs */
.glow-blob-1 {
    position: absolute;
    top: -10%;
    left: -10%;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.12) 0%, transparent 70%);
    filter: blur(80px);
    pointer-events: none;
}
.glow-blob-2 {
    position: absolute;
    bottom: -10%;
    right: -10%;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
    filter: blur(80px);
    pointer-events: none;
}

/* ── Main Unified Container (Premium Light Glassmorphism) ── */
.glass-container {
    width: 100%;
    max-width: 1100px;
    min-height: 680px;
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 24px;
    display: flex;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(31, 41, 55, 0.1);
    z-index: 10;
}

/* ── Left Info Panel ── */
.info-section {
    width: 48%;
    background: rgba(243, 244, 246, 0.4);
    border-right: 1px solid rgba(0, 0, 0, 0.05);
    padding: 56px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.logo-area {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}
.logo-icon {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, #3B82F6, #1D4ED8);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Outfit', sans-serif;
    font-weight: 900;
    font-size: 18px;
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.logo-text {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: 20px;
    color: #111827;
    letter-spacing: -0.3px;
}

.info-content {
    margin: 48px 0;
}
.clinical-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.15);
    border-radius: 100px;
    padding: 6px 14px;
    font-size: 11px;
    font-weight: 700;
    color: #2563EB;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 24px;
}
.clinical-badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #2563EB;
    animation: beacon 2s infinite;
}
@keyframes beacon {
    0% { transform: scale(0.9); opacity: 0.6; }
    50% { transform: scale(1.2); opacity: 1; }
    100% { transform: scale(0.9); opacity: 0.6; }
}

.info-title {
    font-family: 'Outfit', sans-serif;
    font-size: 34px;
    font-weight: 800;
    line-height: 1.2;
    color: #111827;
    margin-bottom: 16px;
}
.info-title span {
    background: linear-gradient(135deg, #3B82F6, #1D4ED8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.info-desc {
    font-size: 14.5px;
    color: #4B5563;
    line-height: 1.6;
    margin-bottom: 36px;
}

/* Feature grid */
.feature-box {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.feature-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px;
    background: rgba(255, 255, 255, 0.5);
    border: 1px solid rgba(0, 0, 0, 0.04);
    border-radius: 12px;
    transition: all 0.2s;
}
.feature-card:hover {
    background: rgba(255, 255, 255, 0.9);
    border-color: rgba(59, 130, 246, 0.25);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
}
.feature-badge-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.feature-badge-icon.blue { background: rgba(59, 130, 246, 0.1); color: #2563EB; }
.feature-badge-icon.emerald { background: rgba(16, 185, 129, 0.1); color: #059669; }
.feature-badge-icon.purple { background: rgba(139, 92, 246, 0.1); color: #7C3AED; }

.feature-title { font-size: 13px; font-weight: 600; color: #1F2937; }
.feature-description { font-size: 12px; color: #6B7280; margin-top: 2px; }

.info-footer {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.footer-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #4B5563;
    font-weight: 500;
}
.footer-badge svg { color: #4B5563; }

/* ── Right Form Panel (Premium Clean Light Theme) ── */
.form-section {
    flex: 1;
    padding: 56px 64px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.form-box {
    width: 100%;
    max-width: 380px;
}

.workspace-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0, 0, 0, 0.06);
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    color: #4B5563;
    margin-bottom: 24px;
}
.workspace-badge svg { color: #10B981; }

.welcome-title {
    font-family: 'Outfit', sans-serif;
    font-size: 28px;
    font-weight: 800;
    color: #111827;
    letter-spacing: -0.5px;
}
.welcome-subtitle {
    font-size: 13.5px;
    color: #6B7280;
    margin-top: 4px;
    margin-bottom: 32px;
}

/* Alert styles */
.clinical-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    border-radius: 8px;
    font-size: 12.5px;
    line-height: 1.4;
    margin-bottom: 20px;
}
.clinical-alert-success {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    color: #065F46;
}
.clinical-alert-error {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.15);
    color: #B91C1C;
}

/* Modern clean text fields */
.form-field {
    margin-bottom: 20px;
}
.field-head {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
}
.field-title {
    font-size: 12px;
    font-weight: 600;
    color: #4B5563;
}
.field-title span { color: #EF4444; }

.input-box {
    position: relative;
}
.input-field-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9CA3AF;
    pointer-events: none;
    transition: color 0.2s;
}
.text-input {
    width: 100%;
    height: 46px;
    padding-left: 42px;
    padding-right: 42px;
    background: #FFFFFF;
    border: 1px solid #D1D5DB;
    border-radius: 10px;
    font-size: 13.5px;
    color: #111827;
    outline: none;
    transition: all 0.2s;
}
.text-input::placeholder { color: #9CA3AF; }
.text-input:focus {
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
.input-box:focus-within .input-field-icon { color: #3B82F6; }

.eye-btn {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9CA3AF;
    cursor: pointer;
    display: flex;
    padding: 0;
    transition: color 0.15s;
}
.eye-btn:hover { color: #4B5563; }

.remember-forgot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.checkbox-container {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}
.checkbox-container input {
    width: 14px;
    height: 14px;
    accent-color: #3B82F6;
    cursor: pointer;
}
.checkbox-container span {
    font-size: 12.5px;
    color: #4B5563;
}
.forgot-link {
    font-size: 12.5px;
    color: #6B7280;
    cursor: default;
}

.submit-button {
    width: 100%;
    height: 46px;
    background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}
.submit-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
}
.submit-button:active {
    transform: translateY(0);
}
.submit-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.auth-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,.3);
    border-top-color: white;
    border-radius: 50%;
    animation: rotate-spin .6s linear infinite;
}
@keyframes rotate-spin { to { transform: rotate(360deg); } }

.clinic-notice {
    margin-top: 32px;
    font-size: 11.5px;
    color: #6B7280;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.clinic-notice svg { color: #10B981; }

/* ── Responsive styling ── */
@media (max-width: 960px) {
    .glass-container {
        flex-direction: column;
        min-height: auto;
    }
    .info-section {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 40px;
    }
    .info-content {
        margin: 24px 0;
    }
    .form-section {
        padding: 40px;
    }
}
@media (max-width: 480px) {
    .info-section, .form-section {
        padding: 24px;
    }
}

[x-cloak] { display: none !important; }
</style>

<div class="login-wrapper" x-data="{ loading: false, showPw: false }">
    <div class="glow-blob-1"></div>
    <div class="glow-blob-2"></div>

    <div class="glass-container">
        
        <!-- Left Section: Clinic Platform Info -->
        <div class="info-section">
            <a href="/" class="logo-area">
                <div class="logo-icon">O</div>
                <span class="logo-text">Orvella</span>
            </a>

            <div class="info-content">
                <div class="clinical-badge">
                    <span class="clinical-badge-dot"></span>
                    Internal Clinical System
                </div>
                <h1 class="info-title">
                    Clinic Consultation<br>
                    & <span>Specialist</span> Diagnostics
                </h1>
                <p class="info-desc">
                    A secure internal workspace for clinical records, specialist referrals, and machine learning assisted screening workflows.
                </p>

                <div class="feature-box">
                    <div class="feature-card">
                        <div class="feature-badge-icon blue">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </div>
                        <div>
                            <div class="feature-title">Scheduling Console</div>
                            <div class="feature-description">Real-time scheduling workflows for patients and practitioners.</div>
                        </div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-badge-icon emerald">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </div>
                        <div>
                            <div class="feature-title">Diagnostic Screening</div>
                            <div class="feature-description">Automated clinical report validation & scan evaluations.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-footer">
                <div class="footer-badge">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    HIPAA Compliant
                </div>
                <div class="footer-badge">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    256-bit SSL
                </div>
            </div>
        </div>

        <!-- Right Section: Credentials Input -->
        <div class="form-section">
            <div class="form-box">
                <div class="workspace-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                    Clinical Gateway Active
                </div>

                <h2 class="welcome-title">Sign In</h2>
                <p class="welcome-subtitle">Access your clinical dashboard workspace</p>

                @if(session('success'))
                    <div class="clinical-alert clinical-alert-success">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;margin-top:1px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="clinical-alert clinical-alert-error">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" @submit="loading = true">
                    @csrf

                    <div class="form-field">
                        <div class="field-head">
                            <label for="username" class="field-title">Username <span>*</span></label>
                        </div>
                        <div class="input-box">
                            <span class="input-field-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <input id="username" name="username" type="text" required autocomplete="off"
                                class="text-input" placeholder="Enter clinical username" value="">
                        </div>
                    </div>

                    <div class="form-field">
                        <div class="field-head">
                            <label for="password" class="field-title">Password <span>*</span></label>
                        </div>
                        <div class="input-box">
                            <span class="input-field-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input id="password" name="password" :type="showPw ? 'text' : 'password'"
                                required autocomplete="new-password"
                                class="text-input" placeholder="••••••••">
                            <button type="button" class="eye-btn" @click="showPw = !showPw" tabindex="-1">
                                <svg x-show="!showPw" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg x-show="showPw" x-cloak width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="remember-forgot">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember">
                            <span>Remember session</span>
                        </label>
                        <span class="forgot-link" title="Contact clinic system administrator for reset support.">Forgot key?</span>
                    </div>

                    <button type="submit" class="submit-button" :disabled="loading">
                        <template x-if="!loading"><span>Authenticate Session</span></template>
                        <template x-if="loading">
                            <span style="display:flex;align-items:center;gap:8px">
                                <span class="auth-spinner"></span>
                                Verifying…
                            </span>
                        </template>
                    </button>
                </form>

                <div class="clinic-notice">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Authorized medical personnel only
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const u = document.getElementById('username');
        const p = document.getElementById('password');
        if (u) u.value = '';
        if (p) p.value = '';
    });
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            const root = document.querySelector('[x-data]');
            if (root?._x_dataStack) root._x_dataStack[0].loading = false;
            const u = document.getElementById('username');
            const p = document.getElementById('password');
            if (u) u.value = '';
            if (p) p.value = '';
        }
    });
</script>

@endsection
