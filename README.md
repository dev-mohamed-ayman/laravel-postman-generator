# Laravel Postman Generator

[![Latest Version](https://img.shields.io/packagist/v/mohamed-ayman/laravel-postman-generator.svg?style=flat-square)](https://packagist.org/packages/mohamed-ayman/laravel-postman-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/mohamed-ayman/laravel-postman-generator.svg?style=flat-square)](https://packagist.org/packages/mohamed-ayman/laravel-postman-generator)
[![License](https://img.shields.io/packagist/l/mohamed-ayman/laravel-postman-generator.svg?style=flat-square)](https://packagist.org/packages/mohamed-ayman/laravel-postman-generator)
[![GitHub Stars](https://img.shields.io/github/stars/dev-mohamed-ayman/laravel-postman-generator.svg?style=flat-square)](https://github.com/dev-mohamed-ayman/laravel-postman-generator)
[![GitHub Forks](https://img.shields.io/github/forks/dev-mohamed-ayman/laravel-postman-generator.svg?style=flat-square)](https://github.com/dev-mohamed-ayman/laravel-postman-generator)

A professional Laravel package that automatically generates Postman collections from your Laravel routes, controllers, validation rules, and middleware. This package scans your entire Laravel project and creates a complete Postman collection with all the necessary information.

## Features

✨ **Comprehensive Route Scanning**
- Automatically scans all Laravel routes
- Supports web, API, and custom route groups
- Configurable route inclusion/exclusion

🔍 **Deep Controller Analysis**
- Extracts controller methods and parameters
- Identifies Form Request classes
- Analyzes method signatures and dependencies

✅ **Validation Rule Extraction**
- Extracts validation rules from Form Requests
- Finds custom validation in controllers
- Generates example request bodies based on validation rules

🛡️ **Middleware Analysis**
- Detects authentication middleware (Sanctum, Passport, etc.)
- Extracts required headers from middleware
- Identifies CSRF protection and other security middleware

📦 **Postman Collection Generation**
- Generates complete Postman Collection v2.1.0 JSON
- Organizes routes into folders by path structure
- Includes variables for base URL and authentication tokens
- Adds default headers and authentication configuration

🌐 **Postman API Integration**
- Update existing Postman collections via API
- Create new collections programmatically
- Sync your Laravel API documentation automatically

## Installation

You can install the package via Composer:

```bash
composer require mohamed-ayman/laravel-postman-generator
```

The package will automatically register its service provider.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=postman-generator-config
```

This will create a `config/postman-generator.php` file where you can configure:

- Base URL for your API
- Collection name and description
- Output path for the generated JSON file
- Routes to include/exclude
- Postman API credentials (for API updates)

## Usage

### Basic Usage

Generate a Postman collection from your Laravel routes:

```bash
php artisan postman:generate
```

This will create a Postman collection JSON file at the configured output path (default: `storage/app/postman-collection.json`).

### Advanced Usage

#### Custom Output Path

```bash
php artisan postman:generate --output=storage/app/my-api-collection.json
```

#### Custom Collection Name

```bash
php artisan postman:generate --name="My Awesome API"
```

#### Custom Base URL

```bash
php artisan postman:generate --base-url=https://api.example.com
```

#### Include Specific Routes

```bash
php artisan postman:generate --include=api --include=web
```

Or include all routes:

```bash
php artisan postman:generate --include=all
```

#### Exclude Routes

```bash
php artisan postman:generate --exclude=telescope --exclude=horizon
```

#### Update Postman Collection via API

First, configure your Postman API key and collection ID in the config file or environment:

```env
POSTMAN_API_KEY=your-api-key-here
POSTMAN_COLLECTION_ID=your-collection-id-here
POSTMAN_WORKSPACE_ID=your-workspace-id-here
```

Then run:

```bash
php artisan postman:generate --update-api --collection-id=your-collection-id
```

### Programmatic Usage

You can also use the package programmatically:

```php
use MohamedAyman\LaravelPostmanGenerator\PostmanGenerator;

$generator = app(PostmanGenerator::class);

// Generate collection
$collection = $generator->generate([
    'collection_name' => 'My API',
    'base_url' => 'https://api.example.com',
    'include_routes' => ['api'],
]);

// Save to file
$generator->saveToFile($collection, storage_path('app/my-collection.json'));

// Update via API
$generator->updateViaApi($collection, [
    'collection_id' => 'your-collection-id',
]);
```

## How It Works

### Route Scanning

The package scans all registered Laravel routes and extracts:
- URI patterns
- HTTP methods
- Route names
- Controller classes and methods
- Middleware stack
- Route parameters

### Controller Analysis

For each controller method, the package:
- Uses reflection to analyze method signatures
- Identifies Form Request classes
- Extracts method parameters and types
- Reads docblocks for additional information

### Validation Extraction

The package extracts validation rules from:
1. **Form Request Classes**: Reads the `rules()` method
2. **Controller Methods**: Parses `$request->validate()` calls
3. **Validator Facade**: Finds `Validator::make()` calls

### Middleware Analysis

The package analyzes middleware to:
- Detect authentication requirements
- Extract required headers (Authorization, CSRF tokens, etc.)
- Identify custom middleware data requirements

### Collection Generation

The generated Postman collection includes:
- All routes organized by path structure
- Request methods and URLs
- Request bodies with example data based on validation rules
- Required headers from middleware
- Route parameters as variables
- Authentication configuration
- Environment variables for base URL and tokens

## Example Generated Collection Structure

```json
{
  "info": {
    "name": "Laravel API Collection",
    "description": "Auto-generated Postman collection from Laravel routes"
  },
  "item": [
    {
      "name": "Users",
      "item": [
        {
          "name": "Get Users",
          "request": {
            "method": "GET",
            "header": [
              {
                "key": "Accept",
                "value": "application/json"
              }
            ],
            "url": {
              "raw": "{{base_url}}/api/users",
              "host": ["{{base_url}}"],
              "path": ["api", "users"]
            }
          }
        },
        {
          "name": "Create User",
          "request": {
            "method": "POST",
            "header": [
              {
                "key": "Authorization",
                "value": "Bearer {{token}}"
              },
              {
                "key": "Content-Type",
                "value": "application/json"
              }
            ],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"name\": \"string\",\n  \"email\": \"example@email.com\",\n  \"password\": \"string\"\n}"
            },
            "url": {
              "raw": "{{base_url}}/api/users",
              "host": ["{{base_url}}"],
              "path": ["api", "users"]
            }
          }
        }
      ]
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost",
      "type": "string"
    },
    {
      "key": "token",
      "value": "",
      "type": "string"
    }
  ]
}
```

## Requirements

- PHP >= 8.2
- Laravel >= 11.0
- Guzzle HTTP Client (for Postman API integration)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Author

**Mohamed Ayman**

## Support

If you encounter any issues or have questions, please open an issue on [GitHub](https://github.com/dev-mohamed-ayman/laravel-postman-generator/issues).

## Repository

[GitHub Repository](https://github.com/dev-mohamed-ayman/laravel-postman-generator)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

