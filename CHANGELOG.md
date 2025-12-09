# Changelog

All notable changes to this project will be documented in this file.

## [1.0.1] - 2024-01-XX

### Fixed
- Fixed null handling in PostmanApiClient
- Fixed duplicate headers issue
- Fixed path grouping for routes
- Fixed route naming for better readability
- Fixed Guzzle exception handling (RequestException vs GuzzleException)

### Improved
- Enhanced validation rule extraction (url, ip, json, in, size)
- Better support for static methods in Form Requests
- Improved header merging (no duplicates)
- Better API route detection (by middleware or URI)
- Enhanced error handling with detailed messages
- Added file size display in command output
- Better route name generation
- Improved path segment filtering

## [1.0.0] - 2024-01-XX

### Added
- Initial release
- Route scanning functionality
- Controller analysis with reflection
- Validation rule extraction from Form Requests and controllers
- Middleware analysis for authentication and headers
- Postman Collection v2.1.0 generation
- Postman API integration for updating collections
- Artisan command for generating collections
- Comprehensive configuration options
- Support for route grouping and organization
- Automatic example data generation based on validation rules

