🥒 Phikl - Apple's Pkl Bridge for PHP
=====================================

[![PHPUnit](https://github.com/alexandre-daubois/phikl/actions/workflows/ci.yaml/badge.svg)](https://github.com/alexandre-daubois/phikl/actions/workflows/ci.yaml)

Phikl (pronounced "_fickle_") is a PHP binding for Apple's PKL language. This library uses the official PKL CLI tool from Apple and
provides a PHP interface to it.

This library is still in development and is not yet ready for production use.

## Installation

You can install this library using composer:

```bash
composer require alexandre-daubois/phikl
```

The CLI tool must be installed on your system. You can either install it manually and set the `PKL_CLI_BIN`
environment variable to the path of the binary or use the `install` subcommand of the `pkl` command to download
the latest supported version of the PKL CLI tool into the `vendor/bin` directory.

```bash
vendor/bin/phikl install
```

You can also set the download location by using the `--location` option:

```bash
vendor/bin/phikl install --location=/usr/local/bin
```

If you do so, you must set the `PKL_CLI_BIN` environment variable to the path of the binary.

## Usage

⚠️ If you plan to use this tool in production, it is highly recommended [to cache the PKL modules](#Caching).

### Using the CLI tool

This package offers a CLI tool to interact with the PKL CLI tool. You can use the `phikl` command to interact with the
PKL CLI tool, among other things.

Here are some examples of how to use the `phikl` command:

```bash
# Install the PKL CLI tool
vendor/bin/phikl install

# Update/Force install the last supported PKL CLI tool
vendor/bin/phikl update

# Print current PKL CLI tool version
vendor/bin/phikl version

# Evaluate one or many PKL file
vendor/bin/phikl eval config/simple.pkl config/nested.pkl
```

### Using Pkl in PHP

The main way to use this library is to evaluate PKL code. You can do this by using the `evaluate` method of the
`Pkl` class.

#### Basic Usage with PklModule

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
use Phikl\Pkl;

$module = Pkl::eval('config/simple.pkl');

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
use Phikl\Pkl;

$module = Pkl::eval('config/nested.pkl');

// you can then interact with the module
echo $module->get('woodPigeon')->get('name'); // Common wood pigeon
echo $module->get('woodPigeon')->get('diet'); // Seeds
echo $module->get('woodPigeon')->get('taxonomy')->get('species'); // Columba palumbus
```

#### Cast to other types

You can cast the values to other types using the `cast` method with a class
representing your data. Let's take the following PKL code:

```pkl
myUser {
    id = 1
    name = "John Doe"
    address {
        street = "123 Main St"
        city = "Springfield"
        state = "IL"
        zip = "62701"
    }
}
```

You can cast this to a `User` class like this:

```php
use Phikl\Pkl;

class User
{
    public int $id;
    public string $name;
    public Address $address;
}

class Address
{
    public string $street;
    public string $city;
    public string $state;
    public string $zip;
}

$module = Pkl::eval('config/user.pkl');
$user = $module->get('myUser')->cast(User::class);
```

You can also pass `User::class` as the second argument to the `eval` method. This will automatically cast the module to
the given class. Beware that it returns an array indexed by the PKL instance name:

```php
use Phikl\Pkl;

// ...

$user = Pkl::eval('config/user.pkl', User::class)['myUser'];
```

#### The `PklProperty` Attribute

You can use the `PklProperty` attribute to specify the name of the property in the PKL file. This is useful when the
property name in the PKL file is different from the property name in the PHP class. Let's take the following PKL code:

```pkl
myUser {
    id = 1
    name = "John Doe"
    address {
        street = "123 Main St"
        city = "Springfield"
        state = "IL"
        zip = "62701"
    }
}
```

You can define a `User` class like this:

```php
use Phikl\PklProperty;

class User
{
    #[PklProperty('id')]
    public int $userId;

    #[PklProperty('name')]
    public string $userName;

    public Address $address;
}
```

When casting, the `PklProperty` attribute will be used to map the property name in the PKL file to the property
name in the PHP class.

## Caching

You can (**and should**) cache the PKL modules to improve performance. This is especially useful when evaluating the same PKL file
multiple times.

**⚠️ Using Phikl with the cache avoids the PKL CLI tool to be executed to evaluate modules and should be done when deploying your application for better performances.**

### Warmup the Cache

You can use the `warmup` command to dump the PKL modules to a cache file by default. Phikl will then use the cache file automatically when evaluating a PKL file. If the PKL file is not found in the cache, Phikl will evaluate the PKL file on the go.

Phikl will go through all `.pkl` files of your project and dump them to the cache file.

Here's an example of how to use the `warmup` command:

```bash
vendor/bin/phikl warmup

# you can also specify the file if you want to use a custom location
# don't forget to set the `PHIKL_CACHE_FILE` environment variable
vendor/bin/phikl warmup --cache-file=cache/pkl.cache
```

If you need to validate a cache file, you can do so by using the `validate-cache` command:

```bash
vendor/bin/phikl validate-cache

# optionally, set the `PHIKL_CACHE_FILE` environment variable
# or use the `--cache-file` option
vendor/bin/phikl validate-cache --cache-file=.cache/.phikl
```

Here are a few things to note about Phikl cache:

- You can disable the cache by calling `Pkl::toggleCache(false)`, which is useful for development but highly discouraged in production
- Phikl will automatically refresh the cache if a PKL module is modified since last warmup
- Any corrupted cache entry will be automatically refreshed

### Cache Backends

If you have your own cache system, you can use the `Pkl::setCache()` method to set the cache system to use. You can pass it any instance of compliant PSR-16 cache system implementing `Psr\SimpleCache\CacheInterface`. This is useful you want to use, for example, a Redis server as a cache system for your Pkl modules.

Phikl comes with the following cache backends:

 * `PersistentCache`, which is the default one used by Phikl. It uses a file to store the cache ;
 * `ApcuCache`, which uses the APCu extension to store the cache in memory ;
 * `MemcachedCache`, which uses the Memcached extension to store the cache in memory.
