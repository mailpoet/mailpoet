<?php declare(strict_types = 1);

namespace MailPoet\Test\Models;

use Codeception\Util\Stub;
use MailPoet\Models\Model as MPModel;
use MailPoetVendor\Idiorm\ORM;

class ModelTest extends \MailPoetTest {
  public function testItRethrowsPDOExceptions() {
    $message = 'Error message';
    $model = Stub::make('MailPoet\Models\Model');
    $mockPDO = $this
      ->getMockBuilder('PDOObject') // @phpstan-ignore-line Mocking PDO on PHP8.1 doesn't work with phpunit 8.5.22
      ->disableOriginalConstructor()
      ->setMethods(["prepare"])
      ->getMock();
    $mockPDO->method('prepare') // @phpstan-ignore-line Mocking PDO on PHP8.1 doesn't work with phpunit 8.5.22
      ->willThrowException(new \PDOException($message));
    ORM::setDb($mockPDO);
    try {
      $model::findMany();
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      verify($e instanceof \PDOException)->false();
      verify($e->getMessage())->equals($message);
    }
  }

  public function testItConvertsModelObjectToArray() {
    $model = MPModel::create();
    $model->first = 'first';
    $model->last = 'last';
    verify($model->asArray('first'))->equals(
      [
        'first' => 'first',
      ]
    );
    verify($model->asArray('last', 'first'))->equals(
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
    verify($model->getErrors())->equals(['error1', 'error2']);
  }

  public function testSetErrorsAsArray() {
    $model = MPModel::create();
    $model->setError(['error1']);
    $model->setError(['error2', 'error1']);
    verify($model->getErrors())->equals(['error1', 'error2']);
  }

  public function testSetErrorsWithCode() {
    $model = MPModel::create();
    $model->setError('error1');
    $model->setError('error2', 5);
    verify($model->getErrors())->equals(['error1', 5 => 'error2']);
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
    verify($errors[MPModel::DUPLICATE_RECORD])->stringContainsString('Please specify a different "name".');
  }

  public function _after() {
    parent::_after();
    ORM::setDb($this->connection->getWrappedConnection());
  }
}
