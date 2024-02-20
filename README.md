PHPKL - Apple's PKL binding in PHP
==================================

This is a PHP binding for Apple's PKL language. This library uses the official PKL CLI tool from Apple and
provides a PHP interface to it.

This library is still in development and is not yet ready for production use.

## Installation

You can install this library using composer:

```bash
composer require alexandre-daubois/phpkl
```

The CLI tool must be installed on your system. You can either install it manually and set the `PKL_CLI_BIN`
environment variable to the path of the binary or use the `--download` option of the `pkl` command to download
the latest supported version of the PKL CLI tool into the `vendor/bin` directory.

```bash
vendor/bin/pkl --download
```

You can also set the download location by using the `--location` option:

```bash
vendor/bin/pkl --download --location=/usr/local/bin
```

If you do so, you must set the `PKL_CLI_BIN` environment variable to the path of the binary.

## Usage

The main way to use this library is to evaluate PKL code. You can do this by using the `evaluate` method of the
`Pkl` class.

Let's say you have the following PKL code:

```pkl
/// config/simple.pkl

name = "Pkl: Configure your Systems in New Ways"
attendants = 100
isInteractive = true
amountLearned = 13.37
```

You can evaluate this code like this:

```php
use Phpkl\Pkl;

$module = Pkl::evaluate('config/simple.pkl');

// you can then interact with the module
echo $module->get('name'); // Pkl: Configure your Systems in New Ways
echo $module->get('attendants'); // 100
echo $module->get('isInteractive'); // true
echo $module->get('amountLearned'); // 13.37
```

This also works with nested modules:

```pkl
/// config/nested.pkl

woodPigeon {
    name = "Common wood pigeon"
    diet = "Seeds"
    taxonomy {
        species = "Columba palumbus"
    }
}
```

```php
use Phpkl\Pkl;

$module = Pkl::evaluate('config/nested.pkl');

// you can then interact with the module
echo $module->get('woodPigeon')->get('name'); // Common wood pigeon
echo $module->get('woodPigeon')->get('diet'); // Seeds
echo $module->get('woodPigeon')->get('taxonomy')->get('species'); // Columba palumbus
```
