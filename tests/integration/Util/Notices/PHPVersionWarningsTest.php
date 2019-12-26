<?php

namespace MailPoet\Util\Notices;

class PHPVersionWarningsTest extends \MailPoetTest {

  /** @var PHPVersionWarnings */
  private $phpVersionWarning;

  public function _before() {
    parent::_before();
    $this->phpVersionWarning = new PHPVersionWarnings();
    delete_transient('dismissed-php-version-outdated-notice');
  }

  public function _after() {
    delete_transient('dismissed-php-version-outdated-notice');
  }

  public function testPHP55IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('5.5.3'))->true();
  }

  public function testPHP56IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('5.6.3'))->true();
  }

  public function testPHP72IsNotOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('7.2'))->false();
  }

  public function testItPrintsWarningFor56() {
    $warning = $this->phpVersionWarning->init('5.6.3', true);
    expect($warning->getMessage())->contains('Your website is running on PHP 5.6.3');
    expect($warning->getMessage())->contains('https://www.mailpoet.com/let-us-handle-your-php-upgrade/');
  }

  public function testItPrintsNoWarningFor70() {
    $warning = $this->phpVersionWarning->init('7.0', true);
    expect($warning)->null();
  }

  public function testItPrintsNoWarningWhenDisabled() {
    $warning = $this->phpVersionWarning->init('5.5.3', false);
    expect($warning)->null();
  }

  public function testItPrintsNoWarningWhenDismised() {
    $this->phpVersionWarning->init('5.5.3', true);
    $this->phpVersionWarning->disable();
    $warning = $this->phpVersionWarning->init('5.5.3', true);
    expect($warning)->null();
  }

}
