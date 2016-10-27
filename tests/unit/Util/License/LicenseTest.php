<?php

use MailPoet\Util\License\License;

class LicenseTest extends MailPoetTest {
  function testItGetsLicense() {
      expect(License::getLicense())->false();
      expect(License::getLicense('valid'))->equals('valid');
  }
}