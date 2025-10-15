# Modular Laravel Package

A robust Laravel package designed to enable modularity, allowing you to organize your application into reusable, self-contained modules with commands similar to Laravel's native artisan commands.

## Table of Contents
- [About](#about)
- [Author](#author)
- [Contact](#contact)
- [Installation](#installation)
- [Usage](#usage)
    - [Creating a Module](#creating-a-module)
    - [Module Commands](#module-commands)
    - [Changing Module Priority](#changing-module-priority)
    - [Integration with Lareon](#integration-with-lareon)
- [Credits](#credits)
- [License](#license)
- [Support](#support)

## About
The **Modular Laravel Package** (teksite/module) brings modularity to Laravel applications, enabling developers to create self-contained modules with their own controllers, models, views, and more. It mirrors Laravel's native artisan commands but prepends `module:` to distinguish module-specific commands. Modules are stored in the `Lareon/Modules` directory, which replicates a miniature Laravel structure for each module.

### Example
To create a controller in a specific module:
```bash
php artisan module:make-controller ExampleController ExampleModule --resource
```

## Author
Developed by **Sina Zangiband**.

## Contact
- Website: [teksite.net](https://teksite.net)
- Email: [sina.zangiband@gmail.com](mailto:sina.zangiband@gmail.com)

## Installation

### Compatibility
| **Laravel** | **Package** |
|-------------|-------------|
| 11.x        | ^1.0        |
| 12.x        | ^2.0        |

### Step 1: Install via Composer
Run the following command in your terminal:
```bash
composer require teksite/module
```

#### Note on wikimedia/composer-merge-plugin
If prompted with:
```
Do you trust "wikimedia/composer-merge-plugin" to execute code and wish to enable it now? (writes "allow-plugins" to composer.json) [y,n,d,?] 
```
Press `y` and Enter. This plugin is required to merge `composer.json` files from modules.

### Step 2: Register the Service Provider
> **Note**: Laravel 5.5 and above supports auto-discovery, so this step is optional for newer versions.

#### For Laravel 10 and 11
Add the service provider to `bootstrap/providers.php`:
```php
<?php

return [
    // Other providers
    Teksite\Module\ModuleServiceProvider::class,
];
```

#### For Laravel 5.x and Earlier
Add the service provider to `config/app.php` under the `providers` array:
```php
'providers' => [
    // Other Service Providers
    Teksite\Module\ModuleServiceProvider::class,
],
```

### Step 3: Publish Configuration (Optional)
Publish the package's configuration file for customization:
```bash
php artisan vendor:publish --provider="Teksite\Module\ModuleServiceProvider"
```

### Step 4: Update Composer.json
To autoload module classes, add the following to your `composer.json`:
```json
"extra": {
    "laravel": {
        "dont-discover": []
    },
    "merge-plugin": {
        "include": [
            "Lareon/Modules/*/composer.json"
        ]
    }
}
```

### Step 5: Refresh Autoloader
Run the following command to refresh Composer's autoloader:
```bash
composer dump-autoload
```

## Usage

### Creating a Module
Generate a new module with a structure similar to Laravel's:
```bash
php artisan module:make Example
```
This creates a new module in `Lareon/Modules/Example` with directories like `Controllers`, `Models`, `Views`, etc.

### Module Commands
The package supports Laravel-like artisan commands prefixed with `module:`. Examples include:
- Create a controller:
  ```bash
  php artisan module:make-controller ExampleController ExampleModule --resource
  ```
- Create a model:
  ```bash
  php artisan module:make-model ExampleModel ExampleModule --migration
  ```
- Create a middleware:
  ```bash
  php artisan module:make-middleware ExampleMiddleware ExampleModule
  ```

### Changing Module Priority
To adjust the loading order of modules, modify the `bootstrap/modules.php` file. Reorder the modules array to prioritize specific modules:
```php
<?php

return [
    'Blog' => [
        'provider' => 'Lareon\\Modules\\Blog\\App\\Providers\\BlogServiceProvider',
        'active' => true,
        'type' => 'lareon',
    ],
    'Page' => [
        'provider' => 'Lareon\\Modules\\Page\\App\\Providers\\PageServiceProvider',
        'active' => true,
        'type' => 'self',
    ],
];
```

### Integration with Lareon
If you use the [teksite/lareon](https://github.com/teksite/lareon) package, you can create modules controlled by Lareon using:
```bash
php artisan module:make Example --lareon
```
To switch an existing module between Lareon-controlled (`lareon`) and self-managed (`self`), update the `type` in `bootstrap/modules.php`:
```php
'Example' => [
    'provider' => 'Lareon\\Modules\\Example\\App\\Providers\\ExampleServiceProvider',
    'active' => true,
    'type' => 'lareon', // or 'self'
],
```
> **Warning**: Manually changing the `type` may cause issues. Ensure compatibility when switching.

## Credits
- [Sina Zangiband](https://github.com/teksite)

## License
This package is open-sourced under the [MIT License](LICENSE.md). See the [License File](LICENSE.md) for details.

## Support
For questions, issues, or feature requests, please reach out via:
- **Website**: [teksite.net](https://teksite.net)
- **Email**: [sina.zangiband@gmail.com](mailto:sina.zangiband@gmail.com)
- **GitHub Issues**: [teksite/module](https://github.com/teksite/module)

Contributions are welcome! Feel free to submit a pull request or open an issue on GitHub.
