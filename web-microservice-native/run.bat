@echo off
REM Menyalakan 3 server sekaligus. Path relatif (%~dp0 = folder run.bat ini).
start "User Service :5001" cmd /k php -S localhost:5001 -t "%~dp0user-service"
start "Product Service :5002" cmd /k php -S localhost:5002 -t "%~dp0product-service"
start "Frontend :8000" cmd /k php -S localhost:8000 -t "%~dp0frontend"
timeout /t 2 >nul
start http://localhost:8000