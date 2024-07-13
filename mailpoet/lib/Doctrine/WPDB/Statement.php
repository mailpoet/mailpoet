<?php declare(strict_types = 1);

namespace MailPoet\Doctrine\WPDB;

use MailPoet\Doctrine\WPDB\Exceptions\NotSupportedException;
use MailPoetVendor\Doctrine\DBAL\Driver\Result;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement as StatementInterface;
use MailPoetVendor\Doctrine\DBAL\ParameterType;

class Statement implements StatementInterface {
  private Connection $connection;
  private string $sql;
  private array $params = [];

  public function __construct(
    Connection $connection,
    string $sql
  ) {
    $this->connection = $connection;
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

    // Convert "?" placeholders to sprintf-like format expected by WPDB (basic implementation).
    // Note that this doesn't parse the SQL query properly and doesn't support named parameters.
    $sql = $this->sql;
    $values = [];
    foreach ($this->params as [$param, $value, $type]) {
      $replacement = $type === ParameterType::INTEGER || ParameterType::BOOLEAN ? '%d' : '%s';
      $pos = strpos($this->sql, '?');
      $sql = substr_replace($this->sql, $replacement, $pos, 1);
      $values[$param] = $value;
    }

    global $wpdb;
    $query = count($values) > 0 ? $wpdb->prepare($sql, $values) : $sql;
    return $this->connection->query($query);
  }
}
