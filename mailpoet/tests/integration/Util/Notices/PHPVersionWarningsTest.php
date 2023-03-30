<?php declare(strict_types = 1);

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
    parent::_after();
    delete_transient('dismissed-php-version-outdated-notice');
  }

  public function testPHP70IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('7.0.8'))->true();
  }

  public function testPHP71IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('7.1.8'))->true();
  }

  public function testPHP72IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('7.2'))->true();
  }

  public function testPHP73IsOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('7.3'))->true();
  }

  public function testPHP74IsNotOutdated() {
    expect($this->phpVersionWarning->isOutdatedPHPVersion('7.4'))->false();
  }

  public function testItPrintsWarningFor71() {
    $warning = $this->phpVersionWarning->init('7.1.0', true);
    expect($warning->getMessage())->stringContainsString('Your website is running an outdated version of PHP (7.1.0)');
    expect($warning->getMessage())->stringContainsString('https://kb.mailpoet.com/article/251-upgrading-the-websites-php-version');
  }

  public function testItPrintsWarningFor72() {
    $warning = $this->phpVersionWarning->init('7.2.0', true);
    expect($warning->getMessage())->stringContainsString('Your website is running an outdated version of PHP (7.2.0)');
    expect($warning->getMessage())->stringContainsString('https://kb.mailpoet.com/article/251-upgrading-the-websites-php-version');
  }

  public function testItPrintsWarningFor73() {
    $warning = $this->phpVersionWarning->init('7.3.0', true);
    expect($warning->getMessage())->stringContainsString('Your website is running an outdated version of PHP (7.3.0)');
    expect($warning->getMessage())->stringContainsString('https://kb.mailpoet.com/article/251-upgrading-the-websites-php-version');
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
