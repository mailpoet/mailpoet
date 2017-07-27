<?php
namespace MailPoet\Test\Models;

use Codeception\Util\Stub;
use MailPoet\Models\Model as MPModel;

class ModelTest extends \MailPoetTest {
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
  }

  function testItConvertsModelObjectToArray() {
    $model = MPModel::create();
    $model->first = 'first';
    $model->last = 'last';
    expect($model->asArray('first'))->equals(
      array(
        'first' => 'first'
      )
    );
    expect($model->asArray('last', 'first'))->equals(
      array(
        'last' => 'last',
        'first' => 'first'
      )
    );
  }

  function _after() {
    \ORM::setDb(null);
  }
}