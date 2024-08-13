<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use MailPoet\Doctrine\ConnectionFactory;
use MailPoet\Doctrine\Middlewares\PostConnectMiddleware;
use MailPoet\Doctrine\Types\JsonOrSerializedType;
use MailPoet\Doctrine\Types\JsonType;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Driver;
use MailPoetVendor\Doctrine\DBAL\Platforms\MySQLPlatform;
use MailPoetVendor\Doctrine\DBAL\Types\Type;

class ConnectionFactoryTest extends \MailPoetTest {
  public function testItSetsUpConnection() {
    $connectionFactory = new ConnectionFactory();
    $connection = $connectionFactory->createConnection();

    verify($connection)->instanceOf(Connection::class);
    verify($connection->getDriver())->instanceOf(Driver::class);
    verify($connection->getDriver())->instanceOf(PostConnectMiddleware::class);
    verify($connection->getDatabasePlatform())->instanceOf(MySQLPlatform::class);

    verify(Type::getType(JsonType::NAME))->instanceOf(JsonType::class);
    verify(Type::getType(JsonOrSerializedType::NAME))->instanceOf(JsonOrSerializedType::class);
  }
}
