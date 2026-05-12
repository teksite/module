# Modular Laravel Package

A comprehensive Laravel package that brings modularity to your applications. It allows you to organize your code into reusable, self-contained modules — each with its own controllers, models, views, and service providers. The package provides Artisan commands similar to Laravel's native ones, prefixed with `module:` for easy module management.

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

The **Modular Laravel Package** (`teksite/module`) enables full modularity in Laravel applications. Modules are stored in the `Lareon/Modules` directory, each replicating a mini Laravel structure. The package mirrors native Laravel Artisan commands but prefixes them with `module:` to avoid conflicts.

> **Note:** From version 3 onward, this package is merged with `teksite/lareon`. Both functionalities are now provided in a single package.

## Author

**Sina Zangiband**

## Contact

- Website: [teksite.net](https://teksite.net) , [laratek.ir](https://laratek.ir) , [laratek.net](https://laratek.net)
- Email: [sina.zangiband@gmail.com](mailto:sina.zangiband@gmail.com)

## Contact
- Website: [teksite.net](https://teksite.net) ,  [laratek.ir](https://laratek.ir) ,  [laratek.net](https://laratek.net)
- Email: [sina.zangiband@gmail.com](mailto:sina.zangiband@gmail.com)

## Installation

### Compatibility

| Laravel Version | Package Version |
|----------------|------------------|
| 11.x           | ^1.0             |
| 12.x           | ^2.0             |
| 13.x           | ^3.0             |

### Step 1: Install via Composer

Run the following command:

```bash
  composer require teksite/module
```

#### Note on `wikimedia/composer-merge-plugin`

If prompted with:

```
Do you trust "wikimedia/composer-merge-plugin" to execute code and wish to enable it now?
```

Press `y` and Enter. This plugin is required to merge `composer.json` files from modules.

#### Note on wikimedia/composer-merge-plugin
If prompted with: `Do you trust "wikimedia/composer-merge-plugin" to execute code and wish to enable it now? (writes "allow-plugins" to composer.json) [y,n,d,?]`
Press `y` and Enter. This plugin is required to merge `composer.json` files from modules.


### Step 2: Register the Service Provider

**Note:** For Laravel 5.5 and above, auto-discovery handles this automatically. The following step is optional for newer versions.

#### Laravel 10 and above

Add the provider to `bootstrap/providers.php`:

```php
<?php

return [
    // ...
    Teksite\Module\ModuleServiceProvider::class,
];
```

#### Laravel 5.x and Earlier

Add the provider to `config/app.php` under `providers`:

```php
'providers' => [
    // ...
    Teksite\Module\ModuleServiceProvider::class,
],
```

### Step 3: Publish Configuration (Optional)

Publish the configuration files for customization:

```bash
  php artisan vendor:publish --provider="Teksite\Module\ModuleServiceProvider"
```
The package publishes two config files:

- `modules.php` – general package behavior
- `modules-hq.php` – control over modules managed by Steward (e.g., routing)

Both are accessible via `config('modules')`.

### Step 4: Update `composer.json`

To enable autoloading of modules and the Steward provider, add the following to your `composer.json`:

```json
"extra": {
    "laravel": {
        "dont-discover": []
    },
    "merge-plugin": {
        "include": [
            "lareon/steward/composer.json",
            "lareon/modules/*/composer.json"
        ]
    }
}
```

### Step 5: Clear Cache

Run the following commands to clear cached data:

```bash
  php artisan cache:clear
  php artisan config:clear
```

### Step 6: Refresh Autoloader

Run Composer's autoload dump:

```bash
  composer dump-autoload
```

### Step 7: Install Steward (Optional but Recommended)

To install Steward, run:

```bash
php artisan module:steward
```

If Steward is not installed, each module must be managed independently.

## Usage

### Creating a Module

Generate a new module with a structure similar to Laravel:

```bash
  php artisan module:make Example
```

Or create a module managed by Steward:

```bash
  php artisan module:make Example --steward
```

- By default, modules are created in `lareon/Modules/{Example}`.
- The `--steward` flag indicates that the module should be managed by Steward (registration of views, configs, routes, etc. are in control of Steward).

### Module Commands

All module‑specific Artisan commands are prefixed with `module:`.

#### Examples

- Create a controller:

```bash
  php artisan module:make-controller ExampleController ExampleModule --resource
```

- Create a model:

```bash
  php artisan module:make-model ExampleModel -a ExampleModule
```

- Create a middleware:

```bash
  php artisan module:make-middleware ExampleMiddleware ExampleModule
```

- And other laravel command.


> **Note:** Replace `ExampleModule` with your actual module name. To create files inside Steward (instead of a module), use `Steward` as the module name.

### Changing Module Priority

To adjust the loading order of modules, edit the `bootstrap/modules.php` file. Reorder the array to prioritize specific modules:

```php
<?php

return [
    'Blog' => [
        'provider' => 'Lareon\\Modules\\Blog\\App\\Providers\\BlogServiceProvider',
        'active'   => true,
        'type'     => 'lareon',
    ],
    'Page' => [
        'provider' => 'Lareon\\Modules\\Page\\App\\Providers\\PageServiceProvider',
        'active'   => true,
        'type'     => 'self',
    ],
];
```

### Change Mode

To switch an existing module between **steward‑controlled** (`steward`) and **self‑managed** (`self`), update the `type` in `bootstrap/modules.php`:

```php
'Example' => [
    'provider' => 'Lareon\\Modules\\Example\\App\\Providers\\ExampleServiceProvider',
    'active'   => true,
    'type'     => 'steward', // change to 'self' for self‑managed or steward to managed by Steward
],
```
and update the `$type` property in `{ModuleName}ServiceProvider`:

```php
/**
 * Module type (self|steward)
 *
 * @var string
 */
protected string $type = "steward"; // change to 'self' for self‑managed or steward to managed by Steward
```

### Integration with Lareon

This package is now merged with [teksite/lareon](https://github.com/teksite/lareon). The main difference in naming is `steward` instead of previous terms like `cms`.

To switch an existing module between steward-controlled (`steward`) and self-managed (`self`), update the `type` in `bootstrap/modules.php`:
```php
'Example' => [
    'provider' => 'Lareon\\Modules\\Example\\App\\Providers\\ExampleServiceProvider',
    'active' => true,
    'type' => 'steward', // or 'self'
],
```
and update {ModuleName}ServiceProvider
```php

    /**
     * Module type (self|steward)
     *
     * @var string
     */
    protected string $type = "steward"; //self to managed by the module itself or steward to manage by Steward

```

> **Warning:** Changing between these two modes generally does not cause issues, provided you do not override internal methods.

## Credits

- [Sina Zangiband](https://github.com/teksite)

## License

This package is open‑sourced under the [MIT License](LICENSE.md).

## Support

For questions, issues, or feature requests, please reach out via:

- **Website**: [teksite.net](https://teksite.net)
- **Email**: [sina.zangiband@gmail.com](mailto:sina.zangiband@gmail.com)
- **GitHub Issues**: [teksite/module](https://github.com/teksite/module)

Contributions are welcome! Feel free to submit a pull request or open an issue on GitHub.
