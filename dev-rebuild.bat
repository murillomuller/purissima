@echo off
echo Rebuilding Purissima PHP Project...
echo.
docker-compose -f docker-compose.windows.yml up --build
echo.
echo Development server ready at http://localhost:8000
echo.
echo To rebuild after making changes, run this script again.
echo.
pause
