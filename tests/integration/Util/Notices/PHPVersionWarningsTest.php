<?php

namespace MailPoet\Util\Notices;

class PHPVersionWarningsTest extends \MailPoetTest {

  /** @var PHPVersionWarnings */
  private $phpVersionWarning;

  function _before() {
    parent::_before();
    $this->phpVersionWarning = new PHPVersionWarnings();
    delete_transient('dismissed-php-version-outdated-notice');
  }

  function _after() {
    delete_transient('dismissed-php-version-outdated-notice');
  }

  function testPHP55IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('5.5.3'))->true();
  }

  function testPHP56IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('5.6.3'))->true();
  }

  function testPHP72IsNotOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('7.2'))->false();
  }

  function testItPrintsWarningFor56() {
    $warning = $this->phpVersionWarning->init('5.6.3', true);
    expect($warning->getMessage())->contains('Your website is running on PHP 5.6.3');
    expect($warning->getMessage())->contains('https://www.mailpoet.com/let-us-handle-your-php-upgrade/');
  }

  function testItPrintsNoWarningFor70() {
    $warning = $this->phpVersionWarning->init('7.0', true);
    expect($warning)->null();
  }

  function testItPrintsNoWarningWhenDisabled() {
    $warning = $this->phpVersionWarning->init('5.5.3', false);
    expect($warning)->null();
  }

  function testItPrintsNoWarningWhenDismised() {
    $this->phpVersionWarning->init('5.5.3', true);
    $this->phpVersionWarning->disable();
    $warning = $this->phpVersionWarning->init('5.5.3', true);
    expect($warning)->null();
  }

}
