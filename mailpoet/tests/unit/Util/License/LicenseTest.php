<?php declare(strict_types = 1);

namespace MailPoet\Test\Util\License;

use MailPoet\Util\License\License;

class LicenseTest extends \MailPoetUnitTest {
  public function testItGetsLicense() {
    if (defined('MAILPOET_PREMIUM_LICENSE')) return;
    expect(License::getLicense())->false();
    expect(License::getLicense('valid'))->equals('valid');
  }
}
