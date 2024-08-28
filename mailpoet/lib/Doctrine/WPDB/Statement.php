<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Exceptions\NotSupportedException;
use MailPoetVendor\Doctrine\DBAL\Driver\Result;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement as StatementInterface;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use MailPoetVendor\Doctrine\DBAL\SQL\Parser;

class Statement implements StatementInterface {
  private Connection $connection;
  private Parser $parser;
  private string $sql;
  private array $params = [];

  public function __construct(
    Connection $connection,
    string $sql
  ) {
    $this->connection = $connection;
    $this->parser = new Parser(false);
    $this->sql = $sql;
  }

  /**
   * @param string|int $param
   * @param mixed $value
   * @param int $type
   * @return true
   */
  public function bindValue($param, $value, $type = ParameterType::STRING) {
    $this->params[$param] = [$param, $value, $type];
    return true;
  }

  /**
   * @param string|int $param
   * @param mixed $variable
   * @param int $type
   * @param int|null $length
   * @return true
   */
  public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null) {
    throw new NotSupportedException(
      'Statement::bindParam() is deprecated in Doctrine and not implemented in WPDB driver. Use Statement::bindValue() instead.'
    );
  }

  public function execute($params = null): Result {
    if ($params !== null) {
      throw new NotSupportedException(
        'Statement::execute() with parameters is deprecated in Doctrine and not implemented in WPDB driver. Use Statement::bindValue() instead.'
      );
    }

    // Convert '?' parameters to WPDB format (sprintf-like: '%s', '%d', ...),
    // and add support for named parameters that are not supported by mysqli.
    $visitor = new ConvertParameters($this->params);
    $this->parser->parse($this->sql, $visitor);
    $sql = $visitor->getSQL();
    $values = $visitor->getValues();

    global $wpdb;
    $query = count($values) > 0
      ? $wpdb->prepare($sql, $values) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
      : $sql;
    return $this->connection->query($query);
  }
}
