@echo off
echo Starting Purissima PHP Project with File Watching...
echo.
echo This will:
echo - Start the development server
echo - Watch for file changes and rebuild automatically
echo - Press Ctrl+C to stop
echo.

:watch
echo Watching for changes...
docker-compose -f docker-compose.dev.yml up --build
echo.
echo Rebuilding due to file changes...
timeout /t 2 /nobreak >nul
goto watch
