<?php

namespace MailPoet\Doctrine\Driver;

use MailPoet\InvalidStateException;
use MailPoetVendor\Doctrine\DBAL\Driver\PDOException;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Exception\InvalidArgumentException;
use MailPoetVendor\Doctrine\DBAL\ParameterType;
use PDO;

class PDOStatement implements \IteratorAggregate, Statement {
  private const PARAM_TYPE_MAP = [
    ParameterType::NULL => PDO::PARAM_NULL,
    ParameterType::INTEGER => PDO::PARAM_INT,
    ParameterType::STRING => PDO::PARAM_STR,
    ParameterType::BINARY => PDO::PARAM_LOB,
    ParameterType::LARGE_OBJECT => PDO::PARAM_LOB,
    ParameterType::BOOLEAN => PDO::PARAM_BOOL,
  ];

  /** @var \PDOStatement */
  private $statement;

  /**
   * Protected constructor.
   */
  public function __construct(\PDOStatement $stmt) {
    $this->statement = $stmt;
  }

  public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null) {
    // This thin wrapper is necessary to shield against the weird signature
    // of PDOStatement::setFetchMode(): even if the second and third
    // parameters are optional, PHP will not let us remove it from this
    // declaration.
    try {
      if ($arg2 === null && $arg3 === null) {
        return $this->statement->setFetchMode($fetchMode);
      }

      if ($arg3 === null) {
        return $this->statement->setFetchMode($fetchMode, $arg2);
      }

      return $this->statement->setFetchMode($fetchMode, $arg2, $arg3);
    } catch (\PDOException $exception) {
      throw new PDOException($exception);
    }
  }

  public function bindValue($param, $value, $type = \PDO::PARAM_STR) {
    $type = $this->convertParamType($type);

    try {
      return $this->statement->bindValue($param, $value, $type);
    } catch (\PDOException $exception) {
      throw new PDOException($exception);
    }
  }

  public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null, $driverOptions = null) {
    if (is_null($type)) $type = ParameterType::STRING;
    $type = $this->convertParamType($type);

    try {
      return $this->statement->bindParam($param, $variable, $type, ...array_slice(func_get_args(), 3));
    } catch (\PDOException $exception) {
      throw new PDOException($exception);
    }
  }

  public function closeCursor() {
    try {
      return $this->statement->closeCursor();
    } catch (\PDOException $exception) {
      // Exceptions not allowed by the interface.
      // In case driver implementations do not adhere to the interface, silence exceptions here.
      return true;
    }
  }

  public function execute($params = null) {
    try {
      return $this->statement->execute($params);
    } catch (\PDOException $exception) {
      throw new PDOException($exception);
    }
  }

  public function fetch($fetchMode = null, $cursorOrientation = null, $cursorOffset = null) {
    try {
      if ($fetchMode === null && $cursorOrientation === null && $cursorOffset === null) {
        return $this->statement->fetch();
      }

      if ($fetchMode !== null && $cursorOrientation === null && $cursorOffset === null) {
        return $this->statement->fetch($fetchMode);
      }

      if ($fetchMode !== null && $cursorOrientation !== null && $cursorOffset === null) {
        return $this->statement->fetch($fetchMode, $cursorOrientation);
      }

      if ($fetchMode !== null && $cursorOrientation !== null && $cursorOffset !== null) {
        return $this->statement->fetch($fetchMode, $cursorOrientation, $cursorOffset);
      }
      throw new InvalidStateException('Invalid arguments for fetch');
    } catch (\PDOException $exception) {
      throw new PDOException($exception);
    }
  }

  public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null) {
    try {
      $data = null;
      if ($fetchMode === null && $fetchArgument === null && $ctorArgs === null) {
        $data = $this->statement->fetchAll();
      }

      if ($fetchMode !== null && $fetchArgument === null && $ctorArgs === null) {
        $data = $this->statement->fetchAll($fetchMode);
      }

      if ($fetchMode !== null && $fetchArgument !== null && $ctorArgs === null) {
        $data = $this->statement->fetchAll($fetchMode, $fetchArgument);
      }

      if ($fetchMode !== null && $fetchArgument !== null && $ctorArgs !== null) {
        $data = $this->statement->fetchAll($fetchMode, $fetchArgument, $ctorArgs);
      }

      if (is_array($data)) {
        return $data;
      } else {
        throw new InvalidStateException('Invalid data returned in fetchAll');
      }
    } catch (\PDOException $exception) {
      throw new PDOException($exception);
    }
  }

  public function fetchColumn($columnIndex = 0) {
    try {
      return $this->statement->fetchColumn($columnIndex);
    } catch (\PDOException $exception) {
      throw new PDOException($exception);
    }
  }

  public function columnCount() {
    return $this->statement->columnCount();
  }

  public function errorCode() {
    return $this->statement->errorCode() ?? '';
  }

  public function errorInfo() {
    return $this->statement->errorInfo();
  }

  public function rowCount() {
    return $this->statement->rowCount();
  }

  public function getIterator() {
    while (($result = $this->statement->fetch()) !== false) {
      yield $result;
    }
  }

  /**
   * Converts DBAL parameter type to PDO parameter type
   *
   * @param int $type Parameter type
   *
   * @throws PDOException
   */
  private function convertParamType(int $type): int {
    if (!isset(self::PARAM_TYPE_MAP[$type])) {
      throw new InvalidArgumentException('Unknown parameter ' . $type);
    }

    return self::PARAM_TYPE_MAP[$type];
  }
}
