<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class GoApiService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.go_api.url', 'http://localhost:8080');
    }

    protected function getHeaders()
    {
        $token = Session::get('api_token');
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    public function login($username, $password)
    {
        $response = Http::post($this->baseUrl . '/login', [
            'username' => $username,
            'password' => $password,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $token = $data['data']['token'] ?? $data['token'] ?? null; // Fallback to token if data wrapper is missing

            // Store token first so getHeaders() works
            Session::put('api_token', $token);

            // Fetch user data via /me using the new token
            $meResponse = Http::withHeaders([
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->get($this->baseUrl . '/me');

            $user = null;
            if ($meResponse->successful()) {
                $meData = $meResponse->json();
                $user = $meData['data']['user'] ?? $meData['user'] ?? null;
            }

            Session::put('user', $user);

            // Return merged data so AuthController can read role
            return array_merge($data, ['user' => $user]);
        }

        return null;
    }

    public function get($endpoint, $query = [])
    {
        return Http::timeout(120)->withHeaders($this->getHeaders())->get($this->baseUrl . $endpoint, $query);
    }

    public function post($endpoint, $data = [])
    {
        return Http::timeout(120)->withHeaders($this->getHeaders())->post($this->baseUrl . $endpoint, $data);
    }

    public function put($endpoint, $data = [])
    {
        return Http::timeout(120)->withHeaders($this->getHeaders())->put($this->baseUrl . $endpoint, $data);
    }

    public function delete($endpoint)
    {
        return Http::timeout(120)->withHeaders($this->getHeaders())->delete($this->baseUrl . $endpoint);
    }

    public function upload($endpoint, $file, $data = [], $paramName = 'file')
    {
        $request = Http::timeout(120)->withHeaders($this->getHeaders())
            ->attach($paramName, file_get_contents($file->getRealPath()), $file->getClientOriginalName());

        foreach ($data as $key => $value) {
            $request->attach($key, $value);
        }

        return $request->post($this->baseUrl . $endpoint);
    }
}
