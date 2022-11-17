<?php declare(strict_types = 1);

namespace MailPoet\Test\WP;

use MailPoet\WP\Readme;

class ReadmeTest extends \MailPoetUnitTest {
  /** @var string */
  private $data;

  public function _before() {
    // Sample taken from https://wordpress.org/plugins/about/readme.txt
    $this->data = (string)file_get_contents(dirname(__FILE__) . '/ReadmeTestData.txt');
  }

  public function testItParsesChangelog() {
    $result = Readme::parseChangelog($this->data);
    expect(count($result))->equals(2);
    expect(count($result[0]['changes']))->equals(2);
    expect(count($result[1]['changes']))->equals(1);
  }

  public function testItRespectsLimitOfParsedItems() {
    $result = Readme::parseChangelog($this->data, 1);
    expect(count($result))->equals(1);
  }

  public function testItReturnsFalseOnMalformedData() {
    $result = Readme::parseChangelog("");
    expect($result)->false();
    $result = Readme::parseChangelog("== Changelog ==\n\n\n=\n==");
    expect($result)->false();
  }
}
