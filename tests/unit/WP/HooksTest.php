<?php
namespace MailPoet\Test\WP;

use MailPoet\WP\Hooks;

class HooksTest extends \MailPoetTest {
  function _before() {
    $this->action = 'mailpoet_test_action';
    $this->filter = 'mailpoet_test_filter';
  }

  function testItCanProcessActions() {
    $test_value = array('abc', 'def');
    $test_value2 = new \stdClass;
    $called = false;

    $callback = function ($value, $value2) use ($test_value, $test_value2, &$called) {
      $called = true;
      expect($value)->same($test_value);
      expect($value2)->same($test_value2);
    };

    Hooks::addAction($this->action, $callback, 10, 2);
    Hooks::doAction($this->action, $test_value, $test_value2);

    expect($called)->true();
  }

  function testItCanProcessFilters() {
    $test_value = array('abc', 'def');

    $called = false;

    $callback = function ($value) use ($test_value, &$called) {
      $called = true;
      return $test_value;
    };

    Hooks::addFilter($this->filter, $callback);
    $result = Hooks::applyFilters($this->filter, $test_value);

    expect($called)->true();
    expect($result)->equals($test_value);
  }
}
