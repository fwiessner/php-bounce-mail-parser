# Bounce email parser

## How to install

Easy way with [Composer](https://getcomposer.org/)-

To install this library, run the command below and you will get the latest version

	composer require sbknk/php-bounce-mail-parser

## How to use it

```php
<?php

// initialize compser autoloader
require_once __DIR__.'/vendor/autoload.php';

$path = 'path/to/mail.txt';
$parser = new PhpBounceMailParser\Parser();

// You can specify a directory
$parser->parseDirectory('path/to/directory');

// or a single file (e.g. *.eml)
$parser->parseFile('path/to/file');

?>
```
