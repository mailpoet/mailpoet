<?php

use MailPoet\Util\License\License;

class LicenseTest extends MailPoetTest {
  function testItGetsLicense() {
    if(defined('MAILPOET_PREMIUM_LICENSE')) return;
    expect(License::getLicense())->false();
    expect(License::getLicense('valid'))->equals('valid');
  }
}