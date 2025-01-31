
# Modular laravel Package

## About
This package has been developed to enable modularity in Laravel. All its commands are similar to Laravel native commands, with the difference that you need to prepend ``module:`` to the commands. Additionally, the name of the created file and the target module should be specified at the beginning of the command, followed by the arguments and options.

By default, teksite/lareon make a directories in the root of the application "``Lareon/Modules``" and generate small version of laravel directories and files, so you can deal with them as you do with Laravel

### example
``
module:make-controller <ControllerName> <ModuleName> <--option>
``

### Author
Sina Zangiband

### Contact
- Website: [laratek.ir](https://laratek.ir)
- Alternate Website: [teksite.net](https://teksite.net)
- email: [sina.zangiband@gmail.com](sina.zangiband@gmail.com)
---

## Installation

| **Laravel** | **package** |
|-------------|-------------|
| 11.0        | ^1.0        |

### Step 1: Install via Composer
Run the following command in your CLI:

```bash
composer require teksite\module
```

### Step 2: Register the Service Provider
> **Note:** This step is not required for newer versions of Laravel (5.x and above) but in case:.

#### Laravel 10 and 11
Add the following line to the `bootstrap/providers` file:

```php
Teksite\Module\ModuleServiceProvider::class,
```

#### Laravel 5.x and earlier
If you are using Laravel 5.x or earlier, register the service provider in the `config/app.php` file under the `providers` array:

```php
'providers' => [
    // Other Service Providers
    Teksite\Handler\ModuleServiceProvider::class,
];
```
### Step 4: publish Service Provider (optional)
Optionally, publish the package's configuration file by running:

```bash
php artisan vendor:publish --provider="teksite\lareon\LareonCmsServiceProvider"
```

### Step 5: add to Composer.json
By default, modules classes are not loaded automatically. You can autoload your modules by adding below codes:


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
},
```
### Step 6: publish Service Provider (optional)
**Tip: do not forget: `composer dump-autoload` .**



---

## How to work with the package

### make a module

```bash
php artisan module:make Example
```

### Change module priority
To change priority of loading modules you can change the order of modules in the ``config/modules``.



---
## Credits

- [Sina Zangiband](https://github.com/teksite)


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

Feel free to reach out if you have any questions or need assistance with this package!
