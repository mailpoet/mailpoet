<?php declare(strict_types = 1);

namespace MailPoet\Config;

use Codeception\Stub;

class InstallerTest extends \MailPoetUnitTest {
  public function testItReturnsRequestArgsUnchangedWhenFilterArgumentsHaveUnexpectedTypes(): void {
    $installer = Stub::make(Installer::class);
    $args = ['method' => 'GET'];
    $releaseUrl = Installer::buildDownloadUrlForVersion('5.45.0');

    verify($installer->authenticateReleaseRequests($args, null))->same($args);
    verify($installer->authenticateReleaseRequests($args, [$releaseUrl]))->same($args);
    verify($installer->authenticateReleaseRequests(null, $releaseUrl))->null();
  }
}
