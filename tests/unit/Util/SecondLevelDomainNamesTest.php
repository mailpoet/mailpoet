<?php

namespace MailPoet\Util;

class SecondLevelDomainNamesTest extends \MailPoetUnitTest {

  /** @var SecondLevelDomainNames */
  private $extractor;

  function _before() {
    parent::_before();
    $this->extractor = new SecondLevelDomainNames();
  }

  function testItGetsSecondLevelDomainName() {
    expect($this->extractor->get('mailpoet.com'))->equals('mailpoet.com');
  }

  function testItGetsSecondLevelDomainNameFromThirdLevel() {
    expect($this->extractor->get('newsletters.mailpoet.com'))->equals('mailpoet.com');
  }

  function testItGetsSecondLevelDomainNameWithCoUk() {
    expect($this->extractor->get('example.co.uk'))->equals('example.co.uk');
  }

  function testItGetsSecondLevelDomainNameFromThirdLevelWithCoUk() {
    expect($this->extractor->get('test.example.co.uk'))->equals('example.co.uk');
  }

  function testItGetsSecondLevelDomainNameForLocalhost() {
    expect($this->extractor->get('localhost'))->equals('localhost');
  }
}
