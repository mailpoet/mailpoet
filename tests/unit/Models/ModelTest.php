<?php

use Codeception\Util\Stub;
use MailPoet\Models\Model;

class ModelTest extends MailPoetTest {
  function testItRethrowsPDOExceptions() {
    $message = 'Error message';
    $model = Stub::make('MailPoet\Models\Model');
    $pdo = Stub::make(
      'PDO',
      array(
        'prepare' => function() use ($message) {
          throw new \PDOException($message);
        }
      )
    );
    \ORM::setDb($pdo);
    try {
      $model::findMany();
      $this->fail('Exception was not thrown');
    } catch(\Exception $e) {
      expect($e instanceof \PDOException)->false();
      expect($e->getMessage())->equals($message);
    }
    // Remove the DB stub
    \ORM::setDb(null);
  }
}
