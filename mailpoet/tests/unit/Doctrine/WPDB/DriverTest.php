<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Exceptions\ConnectionException;
use MailPoetUnitTest;
use MailPoetVendor\Doctrine\DBAL\Driver\API\MySQL\ExceptionConverter;
use MailPoetVendor\Doctrine\DBAL\Platforms\MariaDb1052Platform;
use MailPoetVendor\Doctrine\DBAL\Platforms\MySQLPlatform;

class DriverTest extends MailPoetUnitTest {
  public function testDriverSetup(): void {
    $driver = $this->createPartialMock(Driver::class, ['connect']);

    $this->assertInstanceOf(Connection::class, $driver->connect([]));
    $this->assertInstanceOf(MySQLPlatform::class, $driver->getDatabasePlatform());
    $this->assertInstanceOf(MariaDb1052Platform::class, $driver->createDatabasePlatformForVersion('10.5.8-MariaDB-1:10.5.8+maria~focal'));
    $this->assertInstanceOf(ExceptionConverter::class, $driver->getExceptionConverter());
  }

  public function testConnectFailsWhenWpdbNotInitialized(): void {
    $driver = new Driver();
    $this->expectException(ConnectionException::class);
    $driver->connect([]);
  }
}
