<?php
namespace Helper;
// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
  protected $js_errors = array();

  /**
   * Note: Selenium JS error log buffer is cleared after logs retrieval:
   * https://github.com/SeleniumHQ/selenium/wiki/Logging#retrieval-of-logs
   */
  function seeNoJSErrors() {
    $wd = $this->getModule('WPWebDriver');

    try {
      $logEntries = array_slice(
        $wd->webDriver->manage()->getLog('browser'),
        -15 // Number of log entries to tail
      );

      foreach($logEntries as $logEntry) {
        if($this->isJSError($logEntry)) {
          // Collect JS errors into an array
          $this->js_errors[] = $logEntry['message'];
        }
      }

      if(!empty($this->js_errors)) {
        $this->debug('JS errors : ' . print_r($this->js_errors, true));
      }
    } catch (\Exception $e) {
      $this->debug('Unable to retrieve Selenium logs : ' . $e->getMessage());
    }

    // String comparison is used to show full error messages in test fail diffs
    $this->assertEquals('', join(PHP_EOL, $this->js_errors), 'JS errors are present');
  }

  protected function isJSError($logEntry) {
    return isset($logEntry['level'])
      && isset($logEntry['message'])
      && isset($logEntry['source'])
      && $logEntry['level'] === 'SEVERE'
      && ($logEntry['source'] === 'javascript' // Native JS errors
        || ($logEntry['source'] === 'network' && preg_match('/\.js/i', $logEntry['message'])) // JS scripts failed to load
      );
  }

  function _after() {
    $this->js_errors = array();
  }
}
