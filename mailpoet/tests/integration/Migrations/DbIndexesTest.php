<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use MailPoet\Config\Env;
use MailPoetVendor\Doctrine\DBAL\Connection;

class DbIndexesTest extends \MailPoetTest {
  /**
   * This test checks that we don't have an unique varchar index on a varchar column with a length bigger than 191.
   * The length of 191 is the safe limit that works for all common MySQL configurations.
   * We don't want to allow creating unique indexes on varchar columns with a length bigger than 191.
   * @see https://stackoverflow.com/questions/15157227/mysql-varchar-index-length/16474039#16474039
   */
  public function testDbHasCorrectUniqueVarcharIndexes() {
    $connection = $this->diContainer->get(Connection::class);
    $incorrectIndexes = $connection->executeQuery("SELECT DISTINCT
      ISS.TABLE_NAME,
      ISS.INDEX_NAME,
      ISS.COLUMN_NAME,
      ISS.NON_UNIQUE,
      ISC.CHARACTER_MAXIMUM_LENGTH,
      ISC.DATA_TYPE
    FROM INFORMATION_SCHEMA.STATISTICS ISS
    JOIN INFORMATION_SCHEMA.COLUMNS ISC ON ISC.COLUMN_NAME = ISS.COLUMN_NAME AND ISC.TABLE_NAME = ISS.TABLE_NAME
    WHERE ISS.TABLE_NAME LIKE :prefix
      AND ISS.NON_UNIQUE = 0
      AND ISC.DATA_TYPE = 'varchar'
      AND ISC.CHARACTER_MAXIMUM_LENGTH > 191;",
      ['prefix' => Env::$dbPrefix . '%']
    )->fetchAllAssociative();
    if (!empty($incorrectIndexes)) {
      $this->fail("The following unique indexes use varchar column, but have incorrect length over 191 chars:\n " . json_encode($incorrectIndexes, JSON_PRETTY_PRINT));
    }
  }
}
