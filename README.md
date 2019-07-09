# Puppeteer

## Install

1. `composer require nelson/puppeteer`.
2. Navigate to `%vendorDir%/nelson/puppeteer` and run `npm install` - be sure to do this on the target machine!

## Configuration

1. Enable the extension:
	``` yaml
	extensions:
		puppeteer: Nelson\Puppeteer\DI\Extension
	```
2. Configuration:
	
	``` yaml
	puppeteer:
		tempDir: '%tempDir%/puppeteer/'
		timeout: 120 # seconds
		sandbox: null # or path to the chrome-devel-sandbox binary
		nodeCommand: 'node' # in case multiple versions are installed
	```
	
	These are the default values.
	
## Sandbox

Using sandbox is highly encouraged (by Puppeteer team).

- https://github.com/GoogleChrome/puppeteer/blob/master/docs/troubleshooting.md#alternative-setup-setuid-sandbox

## Usage

Most basic usage:

``` php
$html = '<body style="color: #fff; background: rebeccapurple"><h1>Puppeteer test</h1><p>Some text paragraph</p>';
$html .= '<p>' . date(DateTime::ISO8601) . '</p>';
$html .= '</body>';

/** @var Generator $generator */
$generator = $this->generatorFactory->create();
$output = $generator->generateFromHtml($html, Generator::GENERATE_BOTH);
```

Note: The HTML is not directly passed to the node process. Instead, it is first saved to a temp file and then read by node. This is done intentionally for the generator to deal with large HTML payloads. `nesk/puphpeteer` suffers from this problem, as the data is sent via JSON (AFAIK) and crashes on large HTML, otherwise it works just fine and is a great tool.

There are currently three output modes:

- `Generator::GENERATE_PDF` 
- `Generator::GENERATE_IMAGE`
- `Generator::GENERATE_BOTH` 

These are self-explanatory.

The generator also supports generating from URL:

``` php
$output = $generator->generateFromUrl(new UrlScript('https://www.google.com'), Generator::GENERATE_BOTH);
``` 

Variable `$output` contains:

``` php
array(4) {
  ["pdf"]=>
  string(150) "/xyz/puppeteer/app/../temp/puppeteer/1562653154_-_58f8da81a3c0c3399838891fe88d0db7.pdf"
  ["image"]=>
  string(150) "/xyz/puppeteer/app/../temp/puppeteer/1562653154_-_58f8da81a3c0c3399838891fe88d0db7.png"
  ["command"]=>
  array(7) {
    [0]=>
    string(4) "node"
    [1]=>
    string(91) "/xyz/puppeteer/src/assets/generator.js"
    [2]=>
    string(16) "--inputMode=file"
    [3]=>
    string(159) "--input=/xyz/puppeteer/app/../temp/puppeteer/1562653154_-_58f8da81a3c0c3399838891fe88d0db7.html"
    [4]=>
    string(5) "--pdf"
    [5]=>
    string(7) "--image"
    [6]=>
    string(155) "--output=/xyz/puppeteer/app/../temp/puppeteer/1562653154_-_58f8da81a3c0c3399838891fe88d0db7"
  }
  ["console"]=>
  string(0) ""
}
``` 

Legend:

- `pdf`/`image` are dependant on the mode used.
- `command` - raw command passed from PHP to NODE.js via Symfony/Process.
- `console` - raw output from NODE (via `console.log`). Should be empty in most cases.

 

