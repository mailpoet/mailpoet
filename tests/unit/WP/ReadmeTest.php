<?php
use MailPoet\WP\Readme;

class ReadmeTest extends MailPoetTest {
  function _before() {
    // Sample taken from https://wordpress.org/plugins/about/readme.txt
    $this->data = file_get_contents(dirname(__FILE__) . '/ReadmeTestData.txt');
  }

  function testItParsesChangelog() {
    $result = Readme::parseChangelog($this->data);
    expect(count($result))->equals(2);
    expect(count($result[0]['changes']))->equals(2);
    expect(count($result[1]['changes']))->equals(1);
  }

  function testItRespectsLimitOfParsedItems() {
    $result = Readme::parseChangelog($this->data, 1);
    expect(count($result))->equals(1);
  }

  function testItReturnsFalseOnMalformedData() {
    $result = Readme::parseChangelog("");
    expect($result)->false();
    $result = Readme::parseChangelog("== Changelog ==\n\n\n=\n==");
    expect($result)->false();
  }
}
