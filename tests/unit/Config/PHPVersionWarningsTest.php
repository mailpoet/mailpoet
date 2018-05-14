<?php

namespace MailPoet\Config;

class PHPVersionWarningsTest extends \MailPoetTest {

  /** @var PHPVersionWarnings */
  private $phpVersionWarning;

  function _before() {
    $this->phpVersionWarning = new PHPVersionWarnings();
    delete_transient('dismissed-php-version-outdated-notice');
  }

  function _after() {
    delete_transient('dismissed-php-version-outdated-notice');
  }

  function testItPrintsWarningFor53() {
    $warning = $this->phpVersionWarning->init('5.3.2', true);
    expect($warning)->contains('Your website is running on PHP 5.3.2');
    expect($warning)->notContains('is-dismissible');
  }

  function testItPrintsWarningFor54() {
    $warning = $this->phpVersionWarning->init('5.4.1', true);
    expect($warning)->contains('Your website is running on PHP 5.4.1');
    expect($warning)->notContains('is-dismissible');
  }

  function testItPrintsWarningFor55() {
    $warning = $this->phpVersionWarning->init('5.5.3', true);
    expect($warning)->contains('Your website is running on PHP 5.5.3');
    expect($warning)->contains('is-dismissible');
  }

  function testItPrintsNoWarningFor56() {
    $warning = $this->phpVersionWarning->init('5.6.3', true);
    expect($warning)->null();
  }

  function testItPrintsNoWarningWhenDisabled() {
    $warning = $this->phpVersionWarning->init('5.3.2', false);
    expect($warning)->null();
  }

  function testItPrintsNoWarningWhenDismised() {
    $this->phpVersionWarning->init('5.3.2', true);
    do_action('wp_ajax_dismissed_notice_handler');
    $warning = $this->phpVersionWarning->init('5.3.2', true);
    expect($warning)->null();
  }

}
