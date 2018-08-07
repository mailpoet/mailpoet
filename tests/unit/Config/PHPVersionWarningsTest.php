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

  function testItPrintsWarningFor55() {
    $warning = $this->phpVersionWarning->init('5.5.3', true);
    expect($warning)->contains('Your website is running on PHP 5.5.3');
    expect($warning)->contains('MailPoet will require version 7');
  }


  function testItPrintsWarningFor56() {
    $warning = $this->phpVersionWarning->init('5.6.3', true);
    expect($warning)->contains('Your website is running on PHP 5.6');
    expect($warning)->contains('MailPoet will require version 7');
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
    do_action('wp_ajax_dismissed_notice_handler');
    $warning = $this->phpVersionWarning->init('5.5.3', true);
    expect($warning)->null();
  }

}
