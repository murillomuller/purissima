@echo off
echo 🐳 Setting up Purissima with Docker...

REM Check if Docker is running
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not running. Please start Docker and try again.
    pause
    exit /b 1
)

REM Create .env file if it doesn't exist
if not exist .env (
    echo 📝 Creating .env file from env.example...
    copy env.example .env
)

REM Create storage directories
echo 📁 Creating storage directories...
if not exist storage mkdir storage
if not exist storage\logs mkdir storage\logs
if not exist storage\uploads mkdir storage\uploads
if not exist storage\output mkdir storage\output

REM Build and run with Docker Compose
echo 🚀 Building and starting containers...
docker-compose up --build -d

echo ✅ Setup complete!
echo.
echo 🌐 Application is running at: http://localhost:8000
echo 📋 Available pages:
echo    - Home: http://localhost:8000
echo    - Orders: http://localhost:8000/orders
echo    - PDF Editor: http://localhost:8000/pdf
echo    - About: http://localhost:8000/about
echo.
echo 🛠️  To stop the application: docker-compose down
echo 📊 To view logs: docker-compose logs -f
pause
