# FixedWidth

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]
[![Coverage Status][ico-coverage]][link-coverage]

A simple library to read and write fixed width file. It's a partial port from [this python library](https://github.com/ShawnMilo/fixedwidth), with the aim to be simple, stable and consistent.

## Install

Via Composer

``` bash
$ composer require axelero/fixed-width
```

## Usage

You can read values from arbitrary strings:

``` php

$config = [
'a' => [
    'type' => 'string',
    'start' => 3,
    'length' => 10
],
// numeric indexes of the config array will be ignored
[
    'type' => 'string',
    'start' => 13,
    'end' => 13
],
'b' => [
    'type' => 'string',
    'start' => 14,
    'end' => 17
]
];

$obj = new FixedWidth($config);

$line = '12345678901234567890';
$record = $obj->readLine($line); // ['a' => '3456789012','b' => '4567',]
```

You can write arrays into fixed-width strings:

```php
$config = [
    'a' => [
        'type' => 'string',
        'start' => 1,
        'end' => 5
    ],
    'b' => [
        'type' => 'integer',
        'start' => 6,
        'end' => 10
    ]
];

$obj = new FixedWidth($config);

$data = ['a' => 'xxx', 'b' => 42];
$string = $obj->writeLine($data); // 'xxx  00042'
```

The possible configuration values for each field are:

- **type**: string|integer
- **alignment**: left|right *(defaults to left for string, right for integers)*
- **padding**: charachter to use to fill the missing space *(defaults to '' for string, '0' for integers)*
- **default**: what to write when an array field is missing *(defaults to '' for string, '0' for integers)*

## Testing

``` bash
$ phpunit
```

## Contributing

You are welcome to send any PR. Please make sure the tests pass. Please try to keep the code PSR compliant (in the root of the project lies a .php_cs config file for that).

## Credits

- [axélero][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/axelero/fixed-width.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/axelero/fixed-width/master.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/axelero/fixed-width.svg?style=flat-square
[ico-coverage]: https://img.shields.io/coveralls/axelero/fixed-width.svg

[link-packagist]: https://packagist.org/packages/axelero/fixed-width
[link-travis]: https://travis-ci.org/axelero/fixed-width
[link-scrutinizer]: https://scrutinizer-ci.com/g/axelero/fixed-width/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/axelero/fixed-width
[link-downloads]: https://packagist.org/packages/axelero/fixed-width
[link-author]: https://github.com/axelero
[link-contributors]: ../../contributors
[link-coverage]: https://coveralls.io/r/axelero/fixed-width