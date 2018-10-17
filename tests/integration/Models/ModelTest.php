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

  function testSetErrorsWithoutCode() {
    $model = MPModel::create();
    $model->setError('error1');
    $model->setError('error2');
    expect($model->getErrors())->equals(array('error1', 'error2'));
  }

  function testSetErrorsAsArray() {
    $model = MPModel::create();
    $model->setError(array('error1'));
    $model->setError(array('error2', 'error1'));
    expect($model->getErrors())->equals(array('error1', 'error2'));
  }

  function testSetErrorsWithCode() {
    $model = MPModel::create();
    $model->setError('error1');
    $model->setError('error2', 5);
    expect($model->getErrors())->equals(array('error1', 5 => 'error2'));
  }

  function testSetErrorCodeForDuplicateRecords() {
    $orm = Stub::makeEmpty(
      'ORM',
      array(
        'save' => function() {
          throw new \PDOException("error for key 'name'", MPModel::DUPLICATE_RECORD);
        }
      )
    );
    $model = MPModel::create();
    $model->setError('error1');
    $model->setError('error2', 5);
    $model->set_orm($orm);
    $model->save();
    $errors = $model->getErrors();
    expect($errors)->hasKey(MPModel::DUPLICATE_RECORD);
    expect($errors[MPModel::DUPLICATE_RECORD])->contains('Please specify a different "name".');
  }

  function _after() {
    \ORM::setDb(null);
  }
}