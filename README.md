# Purissima PHP Project

A modern PHP application built with best practices, featuring external API integration and PDF editing capabilities.

## Features

- **External API Integration**: Connect to external APIs with retry logic and error handling
- **PDF Editing**: Upload, edit, and create PDF documents with text overlay
- **Modern Architecture**: Clean MVC structure with dependency injection and services
- **Environment Configuration**: Secure configuration management with environment variables
- **Logging**: Comprehensive logging with Monolog
- **Testing**: PHPUnit test suite included

## Technology Stack

### Backend

- PHP 8.1+
- Composer for dependency management
- Guzzle HTTP for API calls
- FPDF/FPDI for PDF manipulation
- Monolog for logging

### Architecture

- MVC pattern
- Dependency injection
- Service layer
- Router with clean URLs
- Environment configuration

## Installation

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd purissima
   ```

2. **Install dependencies**

   ```bash
   composer install
   ```

3. **Configure environment**

   ```bash
   cp env.example .env
   ```

   Edit `.env` file with your configuration.

4. **Create storage directories** (if not already created)

   ```bash
   mkdir -p storage/logs storage/uploads storage/output
   ```

5. **Set permissions** (Linux/Mac)
   ```bash
   chmod -R 755 storage/
   ```

## Usage

### Development Server

```bash
composer serve
```

This will start a development server at `http://localhost:8000`

### Production

Configure your web server to point to the `public` directory.

## API Endpoints

- `GET /` - Home page
- `GET /about` - About page
- `GET /pdf` - PDF editor interface
- `POST /pdf/upload` - Upload PDF file
- `POST /pdf/edit` - Edit PDF with text overlay
- `GET /pdf/download/{filename}` - Download edited PDF
- `GET /api/users` - Fetch users from external API
- `GET /api/posts` - Fetch posts from external API
- `POST /api/data` - Send data to external API

## Configuration

Edit the `.env` file to configure:

- **Application settings**: APP_NAME, APP_ENV, APP_DEBUG, APP_URL
- **Database settings**: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- **API settings**: API_BASE_URL, API_TIMEOUT, API_RETRY_ATTEMPTS
- **PDF settings**: PDF_UPLOAD_PATH, PDF_OUTPUT_PATH, PDF_MAX_SIZE
- **Logging**: LOG_LEVEL, LOG_FILE

## Testing

Run the test suite:

```bash
composer test
```

## Project Structure

```
purissima/
├── public/                 # Web root
│   └── index.php          # Entry point
├── src/                   # Source code
│   ├── Core/             # Core framework classes
│   ├── Controllers/      # Controllers
│   ├── Services/         # Service classes
│   └── Models/           # Model classes (if needed)
├── views/                # View templates
├── storage/              # Storage directories
│   ├── logs/            # Log files
│   ├── uploads/         # Uploaded files
│   └── output/          # Generated files
├── tests/               # Test files
├── composer.json        # Dependencies
├── .env.example         # Environment template
└── README.md           # This file
```

## Best Practices Implemented

- **PSR-4 Autoloading**: Proper namespace structure
- **Dependency Injection**: Service container for managing dependencies
- **Error Handling**: Comprehensive error handling and logging
- **Security**: Input validation and sanitization
- **Configuration**: Environment-based configuration
- **Testing**: Unit tests with PHPUnit
- **Documentation**: Comprehensive documentation

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).
