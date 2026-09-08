<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use MailPoet\Config\Env;
use MailPoetVendor\Doctrine\DBAL\Connection;

class DbIndexesTest extends \MailPoetTest {
  /**
   * This test checks that we don't index more than 191 characters of any varchar column.
   * The length of 191 is the safe limit that works for all common MySQL configurations:
   * in utf8mb4 it is 764 bytes, just under the 767-byte index limit of InnoDB tables on the
   * COMPACT/REDUNDANT row format. Longer columns must be indexed with a prefix, e.g. `name(191)`.
   * @see https://stackoverflow.com/questions/15157227/mysql-varchar-index-length/16474039#16474039
   */
  public function testDbHasNoVarcharIndexesLongerThan191Characters() {
    $connection = $this->diContainer->get(Connection::class);
    $incorrectIndexes = $connection->executeQuery(
      "SELECT DISTINCT
      ISS.TABLE_NAME,
      ISS.INDEX_NAME,
      ISS.COLUMN_NAME,
      ISS.NON_UNIQUE,
      ISS.SUB_PART,
      ISC.CHARACTER_MAXIMUM_LENGTH,
      ISC.DATA_TYPE
    FROM INFORMATION_SCHEMA.STATISTICS ISS
    JOIN INFORMATION_SCHEMA.COLUMNS ISC ON ISC.COLUMN_NAME = ISS.COLUMN_NAME AND ISC.TABLE_NAME = ISS.TABLE_NAME AND ISC.TABLE_SCHEMA = ISS.TABLE_SCHEMA
    WHERE ISS.TABLE_SCHEMA = DATABASE()
      AND ISS.TABLE_NAME LIKE :prefix
      AND ISC.DATA_TYPE = 'varchar'
      AND COALESCE(ISS.SUB_PART, ISC.CHARACTER_MAXIMUM_LENGTH) > 191;",
      ['prefix' => Env::$dbPrefix . '%']
    )->fetchAllAssociative();
    if (!empty($incorrectIndexes)) {
      $this->fail("The following indexes cover more than 191 characters of a varchar column:\n " . json_encode($incorrectIndexes, JSON_PRETTY_PRINT));
    }
  }
}
