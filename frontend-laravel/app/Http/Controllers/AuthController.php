<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    protected $api;

    public function __construct(GoApiService $api)
    {
        $this->api = $api;
    }

    public function showLoginForm()
    {
        if (Session::has('api_token') && Session::has('user')) {
            return redirect()->route('dashboard');
        }
        
        // If api_token exists but user doesn't, clear the broken session
        if (Session::has('api_token')) {
            Session::flush();
        }
        
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $response = $this->api->login($credentials['username'], $credentials['password']);

        if ($response) {
            // Backend returns role_name (from /me endpoint)
            $role = strtolower($response['user']['role_name'] ?? $response['user']['role'] ?? '');
            
            // Redirect based on role
            switch ($role) {
                case 'admin':
                    return redirect()->route('admin.dashboard');
                case 'doctor':
                    return redirect()->route('doctor.dashboard');
                case 'medical record':
                    return redirect()->route('medrec.dashboard');
                case 'patient':
                    return redirect()->route('patient.dashboard');
                default:
                    return redirect()->route('dashboard');
            }
        }

        return back()->with('error', 'Invalid username or password.')->withInput($request->only('username'));
    }

    public function logout()
    {
        $this->api->post('/logout');
        Session::flush();
        return redirect()->route('home');
    }

    public function showForgotPasswordForm()
    {
        if (Session::has('api_token') && Session::has('user')) {
            return redirect()->route('dashboard');
        }
        return view('auth.forgot-password');
    }

    public function handleForgotPassword(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'new_password' => 'required_if:step,reset|nullable|string|min:6',
            'step' => 'required|in:verify,reset',
        ]);

        $step = $request->input('step', 'verify');

        if ($step === 'verify') {
            // Step 1: Check if username exists
            $response = \Illuminate\Support\Facades\Http::post(
                config('services.go_api.url', 'http://localhost:8080') . '/forgot-password',
                ['username' => $request->username]
            );

            if ($response->successful()) {
                return back()
                    ->withInput(['username' => $request->username])
                    ->with('verified', true)
                    ->with('verified_user', $request->username);
            }

            return back()
                ->withInput()
                ->with('error', 'Username not found. Please check and try again.');
        }

        if ($step === 'reset') {
            $request->validate([
                'new_password' => 'required|string|min:6',
            ]);

            $response = \Illuminate\Support\Facades\Http::post(
                config('services.go_api.url', 'http://localhost:8080') . '/forgot-password',
                [
                    'username' => $request->username,
                    'password' => $request->new_password,
                ]
            );

            if ($response->successful()) {
                return redirect()->route('login')
                    ->with('success', 'Password reset successful! You can now sign in with your new password.');
            }

            return back()
                ->withInput(['username' => $request->username])
                ->with('error', 'Failed to reset password. Please try again.')
                ->with('verified', true)
                ->with('verified_user', $request->username);
        }
    }
}
