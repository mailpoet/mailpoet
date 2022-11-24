<?php

namespace Helper;

use Codeception\Module\WebDriver;
use Codeception\TestInterface;
use PHPUnit\Framework\Assert;

// here you can define custom actions
// all public methods declared in helper class will be available in $I


class Acceptance extends \Codeception\Module {

  protected $jsErrors = [];

  /**
   * Note: Selenium JS error log buffer is cleared after logs retrieval:
   * https://github.com/SeleniumHQ/selenium/wiki/Logging#retrieval-of-logs
   */
  public function seeNoJSErrors() {
    $wd = $this->getModule('WPWebDriver');
    Assert::assertInstanceOf(WebDriver::class, $wd);

    try {
      $logEntries = array_slice(
        $wd->webDriver->manage()->getLog('browser'),
        -15 // Number of log entries to tail
      );

      foreach ($logEntries as $logEntry) {
        if ($this->isJSError($logEntry)) {
          // Collect JS errors into an array
          $this->jsErrors[] = $logEntry['message'];
        }
      }

      if (!empty($this->jsErrors)) {
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions
        $this->debug('JS errors : ' . print_r($this->jsErrors, true));
      }
    } catch (\Exception $e) {
      $this->debug('Unable to retrieve Selenium logs : ' . $e->getMessage());
    }

    // String comparison is used to show full error messages in test fail diffs
    $this->assertEquals('', join(PHP_EOL, $this->jsErrors), 'JS errors are present');
  }

  public function getCurrentUrl() {
    $wd = $this->getModule('WPWebDriver');
    Assert::assertInstanceOf(WebDriver::class, $wd);
    return $wd->_getCurrentUri();
  }

  protected function isJSError($logEntry) {
    return isset($logEntry['level'])
      && isset($logEntry['message'])
      && isset($logEntry['source'])
      && $logEntry['level'] === 'SEVERE'
      && ($logEntry['source'] === 'javascript' // Native JS errors
        || ($logEntry['source'] === 'network' && preg_match('/\.(js|css)/i', $logEntry['message'])) // JS/CSS files failed to load
      );
  }

  public function _after(TestInterface $test) {
    $this->jsErrors = [];
  }
}
