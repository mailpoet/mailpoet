<?php

namespace MailPoet\Test\Models;

use Codeception\Util\Stub;
use MailPoet\Models\Model as MPModel;
use MailPoetVendor\Idiorm\ORM;

class ModelTest extends \MailPoetTest {
  public function testItRethrowsPDOExceptions() {
    $message = 'Error message';
    $model = Stub::make('MailPoet\Models\Model');
    $pdo = Stub::make(
      'PDO',
      [
        'prepare' => function() use ($message) {
          throw new \PDOException($message);
        },
      ]
    );
    ORM::setDb($pdo);
    try {
      $model::findMany();
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      expect($e instanceof \PDOException)->false();
      expect($e->getMessage())->equals($message);
    }
  }

  public function testItConvertsModelObjectToArray() {
    $model = MPModel::create();
    $model->first = 'first';
    $model->last = 'last';
    expect($model->asArray('first'))->equals(
      [
        'first' => 'first',
      ]
    );
    expect($model->asArray('last', 'first'))->equals(
      [
        'last' => 'last',
        'first' => 'first',
      ]
    );
  }

  public function testSetErrorsWithoutCode() {
    $model = MPModel::create();
    $model->setError('error1');
    $model->setError('error2');
    expect($model->getErrors())->equals(['error1', 'error2']);
  }

  public function testSetErrorsAsArray() {
    $model = MPModel::create();
    $model->setError(['error1']);
    $model->setError(['error2', 'error1']);
    expect($model->getErrors())->equals(['error1', 'error2']);
  }

  public function testSetErrorsWithCode() {
    $model = MPModel::create();
    $model->setError('error1');
    $model->setError('error2', 5);
    expect($model->getErrors())->equals(['error1', 5 => 'error2']);
  }

  public function testSetErrorCodeForDuplicateRecords() {
    $orm = Stub::makeEmpty(
      ORM::class,
      [
        'save' => function() {
          throw new \PDOException("error for key 'name'", MPModel::DUPLICATE_RECORD);
        },
      ]
    );
    $model = MPModel::create();
    $model->setError('error1');
    $model->setError('error2', 5);
    $model->set_orm($orm);
    $model->save();
    $errors = $model->getErrors();
    expect($errors)->hasKey(MPModel::DUPLICATE_RECORD);
    expect($errors[MPModel::DUPLICATE_RECORD])->stringContainsString('Please specify a different "name".');
  }

  public function _after() {
    ORM::setDb($this->connection->getWrappedConnection());
  }
}
