<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Services\GoApiService;

$baseUrl = config('services.go_api.url', 'http://localhost:8080');
$response = Illuminate\Support\Facades\Http::post($baseUrl . '/login', [
    'username' => 'dr_tirta',
    'password' => 'doctor123',
]);
echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";

$data = $response->json();
$token = $data['data']['token'] ?? $data['token'] ?? null;
if ($token) {
    echo "Login successful! Token: " . substr($token, 0, 10) . "...\n";
    $api = new GoApiService();
    // Use reflection or inject session to bypass Session::put if needed, or set session
    Illuminate\Support\Facades\Session::put('api_token', $token);
    $response = $api->post('/scans/9/analyze', []);
    echo "Analyze Response Status: " . $response->status() . "\n";
    echo "Analyze Response Body: " . $response->body() . "\n";
} else {
    echo "Login failed!\n";
}
