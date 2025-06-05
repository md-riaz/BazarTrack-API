# Contributing Guidelines

## Coding style
- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/).
- Use camelCase for variable and method names.
- Indent using four spaces.

## Composer
This project does not require Composer by default. If you need additional dependencies:
1. Ensure a `composer.json` file exists at the project root.
2. Run `composer install` to fetch dependencies.
3. Run `composer dump-autoload` to refresh the autoloader.

## Commit messages
- Begin with a short summary line (50 characters or less).
- Optionally follow with a blank line and a more detailed description.
- Reference related issues using `#` followed by the issue number.

## Testing
Run the test suite using:

```bash
./vendor/bin/phpunit
```

To check PHP syntax:

```bash
php -l path/to/file.php
```
