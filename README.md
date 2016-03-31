# CredStash for PHP

This is a PHP port of original [CredStash][cs] (written in python).
Encryption and DynamoDB storage are compatible with python version so both can work side by side.
There is an optional CLI tool as well, details below.

More information about what CredStash is, how it works, and how to set it up can be read in their [README][cs].

## Installation

```txt
$ composer require gmo/credstash
```

## PHP Usage

#### Create CredStash instance
The easiest way to create CredStash is with the AWS SDK object:
```php
<?php

use CredStash\CredStash;

$sdk = new Aws\Sdk(); // config omitted

$credStash = CredStash::createFromSdk($sdk);

```

#### Getting individual secrets:
```php
// Get secret for "foo" credential
$secret = $credStash->get('foo');

// Including context parameters
$secret = $credStash->get('foo', ['env' => 'prod']);

// By default, the latest version is used,
// but a specific version can be passed in.
$secret = $credStash->get('foo', [], 2);
```

#### Getting multiple secrets:
```php
// Get latest version of all secrets
$secrets = $credStash->getAll(); // ['foo' => 'secret', 'bar' => 'another secret'];

// Including context parameters
$secrets = $credStash->getAll(['env' => 'prod']);

// Get specific version for all secrets
$secrets = $credStash->getAll([], 2);

// Get all secrets matching pattern
$secrets = $credStash->search('ssl.*'); // matches "ssl.foo" and "ssl.bar"

// This version also allows "?" and "[]" patterns.
$secrets = $credStash->search('s?l'); // matches "ssl" and "sdl"
$secrets = $credStash->search('gr[ae]y'); // matches "gray" and "grey"

// Context and version can specified here as well
$secrets = $credStash->search($pattern, $context, $version);
```

#### Putting secrets:
```php
// Put secret into store at the next highest version
$credStash->put('foo', 'secret');

// Including context parameters
$credStash->put('foo', 'secret', ['env' => 'prod']);

// Put secret into store at a specified version
$credStash->put('foo', 'secret', [], 2);
```

#### Deleting secrets:
```php
$credStash->delete('foo');
```

#### Listing credentials and their latest versions:
```php
$credentials = $credStash->listCredentials(); // ['foo' => '000000000000000002', 'bar' => '000000000000000003'];
// As you can see versions are padded to ensure sorting is consistent

// They can be optionally converted to integers though
// with the by passing false to the $pad parameter.
$credentials = $credStash->listCredentials(false); // ['foo' => 2, 'bar' => 3];
```

## CLI Usage

**Note:** CLI tool requires Symfony's Console Component to be installed manually, 
because this is an optional dependency.
```bash
$ composer require symfony/console 
```

The CLI tool is compatible with the python version with a couple differences due to
compatibility with Symfony's Console Application's standard commands/parameters.

#### Version parameter:
The python version has `-v` or `--version` to specify the version to `put` or `get`.
Here it is `-c` or `--cred-version`, because Symfony uses this for the version of the _console tool_.

#### List command
The python version's `list` command is renamed to `info` here.
Symfony has a list command that lists the commands available.

Other than these two differences they are exactly the same.

More info can be found in their [README][cs-cli] or by running this tool without any arguments.
Info for each command can be viewed with standard `help` command or `-h`/`--help` parameter.

[cs]: https://github.com/fugue/credstash
[cs-cli]: https://github.com/fugue/credstash#usage
