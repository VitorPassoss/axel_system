# tc-lib-barcode
*PHP barcode library*

[![Latest Stable Version](https://poser.pugx.org/tecnickcom/tc-lib-barcode/version)](https://packagist.org/packages/tecnickcom/tc-lib-barcode)
![Build](https://github.com/tecnickcom/tc-lib-barcode/actions/workflows/check.yml/badge.svg)
[![Coverage](https://codecov.io/gh/tecnickcom/tc-lib-barcode/graph/badge.svg?token=PW6r97iVuW)](https://codecov.io/gh/tecnickcom/tc-lib-barcode)
[![License](https://poser.pugx.org/tecnickcom/tc-lib-barcode/license)](https://packagist.org/packages/tecnickcom/tc-lib-barcode)
[![Downloads](https://poser.pugx.org/tecnickcom/tc-lib-barcode/downloads)](https://packagist.org/packages/tecnickcom/tc-lib-barcode)

[![Donate via PayPal](https://img.shields.io/badge/donate-paypal-87ceeb.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&currency_code=GBP&business=paypal@tecnick.com&item_name=donation%20for%20tc-lib-barcode%20project)
*Please consider supporting this project by making a donation via [PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&currency_code=GBP&business=paypal@tecnick.com&item_name=donation%20for%20tc-lib-barcode%20project)*

* **category**    Library
* **package**     \Com\Tecnick\Barcode
* **author**      Nicola Asuni <info@tecnick.com>
* **copyright**   2001-2024 Nicola Asuni - Tecnick.com LTD
* **license**     http://www.gnu.org/copyleft/lesser.html GNU-LGPL v3 (see LICENSE.TXT)
* **link**        https://github.com/tecnickcom/tc-lib-barcode
* **SRC DOC**     https://tcpdf.org/docs/srcdoc/tc-lib-barcode

## Description

This library includes utility PHP classes to generate linear and bidimensional barcodes:

* C39        : CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9
* C39+       : CODE 39 with checksum
* C39E       : CODE 39 EXTENDED
* C39E+      : CODE 39 EXTENDED + CHECKSUM
* C93        : CODE 93 - USS-93
* S25        : Standard 2 of 5
* S25+       : Standard 2 of 5 + CHECKSUM
* I25        : Interleaved 2 of 5
* I25+       : Interleaved 2 of 5 + CHECKSUM
* C128       : CODE 128
* C128A      : CODE 128 A
* C128B      : CODE 128 B
* C128C      : CODE 128 C
* EAN2       : 2-Digits UPC-Based Extension
* EAN5       : 5-Digits UPC-Based Extension
* EAN8       : EAN 8
* EAN13      : EAN 13
* UPCA       : UPC-A
* UPCE       : UPC-E
* MSI        : MSI (Variation of Plessey code)
* MSI+       : MSI + CHECKSUM (modulo 11)
* POSTNET    : POSTNET
* PLANET     : PLANET
* RMS4CC     : RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code)
* KIX        : KIX (Klant index - Customer index)
* IMB        : IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200
* IMBPRE     : IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200- pre-processed
* CODABAR    : CODABAR
* CODE11     : CODE 11
* PHARMA     : PHARMACODE
* PHARMA2T   : PHARMACODE TWO-TRACKS
* AZTEC      : AZTEC Code (ISO/IEC 24778:2008)
* DATAMATRIX : DATAMATRIX (ISO/IEC 16022)
* PDF417     : PDF417 (ISO/IEC 15438:2006)
* QRCODE     : QR-CODE
* RAW        : 2D RAW MODE comma-separated rows
* RAW2       : 2D RAW MODE rows enclosed in square parentheses

### Output Formats

* PNG Image
* SVG Image
* HTML DIV
* Unicode String
* Binary String

The initial source code has been derived from [TCPDF](<http://www.tcpdf.org>).


## Getting started

First, you need to install all development dependencies using [Composer](https://getcomposer.org/):

```bash
$ curl -sS https://getcomposer.org/installer | php
$ mv composer.phar /usr/local/bin/composer
```

This project include a Makefile that allows you to test and build the project with simple commands.
To see all available options:

```bash
make help
```

To install all the development dependencies:

```bash
make deps
```

## Running all tests

Before committing the code, please check if it passes all tests using

```bash
make qa
```

All artifacts are generated in the target directory.


## Examples

Examples are located in the `example` directory.

Start a development server (requires PHP 8.0+) using the command:

```
make server
```

and point your browser to <http://localhost:8000/index.php>


### Simple Code Example

Please check example/index.php for a full example.

```
// instantiate the barcode class
$barcode = new \Com\Tecnick\Barcode\Barcode();

// generate a barcode
$bobj = $barcode->getBarcodeObj(
    'QRCODE,H',                     // barcode type and additional comma-separated parameters
    'https://tecnick.com',          // data string to encode
    -4,                             // bar width (use absolute or negative value as multiplication factor)
    -4,                             // bar height (use absolute or negative value as multiplication factor)
    'black',                        // foreground color
    array(-2, -2, -2, -2)           // padding (use absolute or negative values as multiplication factors)
    )->setBackgroundColor('white'); // background color

// output the barcode as HTML div (see other output formats in the documentation and examples)
echo $bobj->getHtmlDiv();
```


## Installation

Create a composer.json in your projects root-directory:

```json
{
    "require": {
        "tecnickcom/tc-lib-barcode": "^2.3"
    }
}
```

Or add to an existing project with: 

```bash
composer require tecnickcom/tc-lib-barcode ^2.3
```

## Packaging

This library is mainly intended to be used and included in other PHP projects using Composer.
However, since some production environments dictates the installation of any application as RPM or DEB packages,
this library includes make targets for building these packages (`make rpm` and `make deb`).
The packages are generated under the `target` directory.

When this library is installed using an RPM or DEB package, you can use it your code by including the autoloader:
```php
require_once ('/usr/share/php/Com/Tecnick/Barcode/autoload.php');
```


## Developer(s) Contact

* Nicola Asuni <info@tecnick.com>
