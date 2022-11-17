<?php declare(strict_types = 1);

namespace MailPoet\Util;

class SecondLevelDomainNamesTest extends \MailPoetUnitTest {

  /** @var SecondLevelDomainNames */
  private $extractor;

  public function _before() {
    parent::_before();
    $this->extractor = new SecondLevelDomainNames();
  }

  public function testItGetsSecondLevelDomainName() {
    expect($this->extractor->get('mailpoet.com'))->equals('mailpoet.com');
  }

  public function testItGetsSecondLevelDomainNameFromThirdLevel() {
    expect($this->extractor->get('newsletters.mailpoet.com'))->equals('mailpoet.com');
  }

  public function testItGetsSecondLevelDomainNameWithCoUk() {
    expect($this->extractor->get('example.co.uk'))->equals('example.co.uk');
  }

  public function testItGetsSecondLevelDomainNameFromThirdLevelWithCoUk() {
    expect($this->extractor->get('test.example.co.uk'))->equals('example.co.uk');
  }

  public function testItGetsSecondLevelDomainNameForLocalhost() {
    expect($this->extractor->get('localhost'))->equals('localhost');
  }
}
