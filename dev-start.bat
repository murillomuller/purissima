@echo off
echo Starting Purissima PHP Project with Hot Reload...
echo.
echo This will:
echo - Start the PHP development server
echo - Rebuild when you make changes (run this script again)
echo.
echo Press Ctrl+C to stop
echo.

docker-compose -f docker-compose.windows.yml up --build
