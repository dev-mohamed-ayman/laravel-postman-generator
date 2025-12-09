# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability, please send an email to devel.mohamed.ayman@gmail.com. All security vulnerabilities will be promptly addressed.

Please do not open public issues for security vulnerabilities.

## Security Best Practices

When using this package:

1. **Never commit API keys**: Always use environment variables
2. **Keep dependencies updated**: Regularly update the package and its dependencies
3. **Review generated collections**: Always review the generated Postman collections before sharing
4. **Use HTTPS**: Always use HTTPS when making API requests

## Security Considerations

- The package reads route and controller information from your Laravel application
- Generated Postman collections may contain sensitive endpoint information
- Review and sanitize collections before sharing publicly
- API keys should be stored securely and never committed to version control

