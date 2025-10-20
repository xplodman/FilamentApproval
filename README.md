# A Filament plugin for approval / moderation of create/edit/delete operations.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xplodman/filamentapproval.svg?style=flat-square)](https://packagist.org/packages/xplodman/filamentapproval)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/xplodman/filamentapproval/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xplodman/filamentapproval/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/xplodman/filamentapproval/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/xplodman/filamentapproval/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/xplodman/filamentapproval.svg?style=flat-square)](https://packagist.org/packages/xplodman/filamentapproval)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require xplodman/filamentapproval
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filamentapproval-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filamentapproval-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filamentapproval-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentApproval = new Xplodman\FilamentApproval();
echo $filamentApproval->echoPhrase('Hello, Xplodman!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Xplodman](https://github.com/xplodman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
