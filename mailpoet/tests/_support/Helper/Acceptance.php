<?php declare(strict_types = 1);

namespace Helper;

use Codeception\Module\WebDriver;
use Codeception\TestInterface;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use PHPUnit\Framework\Assert;

// here you can define custom actions
// all public methods declared in helper class will be available in $I


class Acceptance extends \Codeception\Module {

  protected $jsErrors = [];

  /**
   * php-webdriver 1.16 leaves webSocketUrl out of the capabilities it sends, so the
   * WebDriver BiDi session set up in acceptance.suite.yml would never start.
   * Remove once https://github.com/php-webdriver/php-webdriver/pull/1153 is released.
   *
   * This runs before every test, not once per suite: WebDriver::amOnUrl() reconfigures
   * the module, which rebuilds the capabilities from the suite config.
   */
  public function _before(TestInterface $test) {
    $webDriver = $this->getModule('WPWebDriver');
    Assert::assertInstanceOf(WebDriver::class, $webDriver);
    $webDriver->_capabilities(function ($capabilities): DesiredCapabilities {
      if ($capabilities instanceof DesiredCapabilities) {
        $capabilities = $capabilities->toArray();
      }
      return new class($capabilities) extends DesiredCapabilities {
        public function toW3cCompatibleArray() {
          $w3cCapabilities = parent::toW3cCompatibleArray();
          $webSocketUrl = $this->getCapability('webSocketUrl');
          if ($webSocketUrl !== null) {
            $w3cCapabilities['webSocketUrl'] = $webSocketUrl;
          }
          return $w3cCapabilities;
        }
      };
    });
  }

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
