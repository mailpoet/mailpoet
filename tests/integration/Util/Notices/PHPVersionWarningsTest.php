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

  public function testPHP56IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('5.6.3'))->true();
  }

  public function testPHP70IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('7.0.8'))->true();
  }

  public function testPHP71IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('7.1.8'))->true();
  }

  public function testPHP73IsNotOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('7.3'))->false();
  }

  public function testItPrintsWarningFor70() {
    $warning = $this->phpVersionWarning->init('7.0.0', true);
    expect($warning->getMessage())->contains('Your website is running on PHP 7.0.0');
    expect($warning->getMessage())->contains('https://www.mailpoet.com/let-us-handle-your-php-upgrade/');
  }

  public function testItPrintsWarningFor71() {
    $warning = $this->phpVersionWarning->init('7.1.0', true);
    expect($warning->getMessage())->contains('Your website is running on PHP 7.1.0');
    expect($warning->getMessage())->contains('https://www.mailpoet.com/let-us-handle-your-php-upgrade/');
  }

  public function testItPrintsNoWarningFor72() {
    $warning = $this->phpVersionWarning->init('7.2', true);
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
