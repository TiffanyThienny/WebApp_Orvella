@echo off
color 0B
echo ===================================================
echo        ORVELLA - HEALTHCARE SYSTEM LAUNCHER
echo ===================================================
echo.
echo Please ensure your MySQL Database (e.g. XAMPP) is running!
echo.
timeout /t 3 /nobreak >nul

echo [1/3] Starting Go Backend API (Port 8080)...
start "Go API Backend" cmd /k "cd backend && go run cmd/api/main.go"
timeout /t 3 /nobreak >nul

echo [2/3] Starting Python AI Service (Port 8002)...
start "Python AI Service" cmd /k "cd ai-service && pip install -r requirements.txt && python main.py"
timeout /t 3 /nobreak >nul

echo [3/3] Starting Laravel Frontend (Port 8001)...
start "Laravel Frontend" cmd /k "cd frontend-laravel && php artisan serve --port=8001"
timeout /t 3 /nobreak >nul

echo.
echo ===================================================
echo ALL SERVICES STARTED SUCCESSFULLY!
echo ===================================================
echo.
echo 1. Go API is running on         http://localhost:8080
echo 2. Python AI is running on      http://localhost:8002
echo 3. Laravel Frontend is running on http://127.0.0.1:8001
echo.
echo Access your Medical Dashboard here:
echo http://127.0.0.1:8001
echo.
pause
