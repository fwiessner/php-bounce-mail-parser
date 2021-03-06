# Bounce email parser

## How to install with [Composer](https://getcomposer.org/)

To install this library, run the command below and you will get the latest version

	composer require fwiessner/php-bounce-mail-parser

## How to use it

```php
<?php

// Initialize compser autoloader
require_once __DIR__ . '/vendor/autoload.php';

$parser = new \PhpBounceMailParser\Parser();

// You can specify a directory
$parser->parseDirectory('path/to/directory');

// or a single file (e.g. *.eml)
$parser->parseFile('path/to/file');

// or a single email loaded as string (e.g. via imap_open / imap_fetchbody)
$parser->parseData(explode("\n",$emailstring);

// Specify emails to be ignored when trying to find the recipient as follows
$parser->ignoreEmail('no-reply@wf-ingbau.de');

// Finally get the data output directly in the browser with simple echo
$parser->outputCsv();

// return the Csv as string:
$parser->returnCsv();

// or as file download
$parser->saveCsvAs();

// Here is a complete working example
$parser = new \PhpBounceMailParser\Parser();
$parser->ignoreEmail('foo@bar.com')
       ->parseDirectory(__DIR__ . '/resources')
       ->outputCsv();

?>
```
