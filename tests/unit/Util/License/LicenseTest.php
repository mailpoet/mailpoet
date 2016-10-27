<?php

use MailPoet\Util\License\License;

class LicenseTest extends MailPoetTest {
  function testItGetsLicense() {
    if(!defined('MAILPOET_PREMIUM_LICENSE')) {
      expect(License::getLicense())->false();
      define('MAILPOET_PREMIUM_LICENSE', 'valid');
      expect(License::getLicense())->equals('valid');
    }
    else {
      expect(License::getLicense())->equals(MAILPOET_PREMIUM_LICENSE);
    }
  }

  function testItAddsFeaturePermissionCheckAction() {
    remove_all_actions(License::CHECK_PERMISSION);
    expect(has_action(License::CHECK_PERMISSION))->false();
    $license = new License();
    $license->init();
    expect(has_action(License::CHECK_PERMISSION))->true();
  }
}