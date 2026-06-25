<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\Middlewares;

use MailPoetVendor\Doctrine\DBAL\Driver\Connection;
use MailPoetVendor\Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Throwable;

class PostConnectMiddleware extends AbstractDriverMiddleware {
  public function connect(array $params): Connection {
    $connection = parent::connect($params);
    $connection->exec('SET time_zone = "+00:00"');
    // Lift the session MAX_JOIN_SIZE limit so large best-effort joins (e.g. the WP user
    // synchronization on sites with many users/subscribers) don't fatal on hosts that
    // enforce a low MAX_JOIN_SIZE. Best-effort: some hosts/engines may disallow it.
    try {
      $connection->exec('SET SESSION SQL_BIG_SELECTS=1');
    } catch (Throwable $e) {
      // Ignore — keep the connection usable even if the host rejects this statement.
    }
    return $connection;
  }
}
