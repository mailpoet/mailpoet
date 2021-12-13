<?php

namespace MailPoet\Listing;

use MailPoet\Models\Subscriber;

require_once('BulkActionClassStub.php');

class BulkActionFactoryTest extends \MailPoetTest {
  /** @var BulkActionFactory */
  private $bulkActionFactory;

  public function _before() {
    $this->bulkActionFactory = new BulkActionFactory();
  }

  public function testItReturnsCustomActionClass() {
    $modelClass = Subscriber::class;
    $method = 'bulkTestAction';
    $actionClass = new BulkActionClassStub;
    $this->bulkActionFactory->registerAction($modelClass, $method, $actionClass);
    $resultingClass = $this->bulkActionFactory->getActionClass($modelClass, $method);
    expect($resultingClass)->equals($actionClass);
  }

  public function testItThrowsIfANonExistentActionMethodIsBeingRegistered() {
    $modelClass = Subscriber::class;
    $method = 'bulkDoesNotExist';
    $actionClass = new BulkActionClassStub;
    try {
      $this->bulkActionFactory->registerAction($modelClass, $method, $actionClass);
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->stringContainsString('has no method');
    }
  }

  public function testItThrowsIfANonExistentActionClassIsBeingRegistered() {
    $modelClass = Subscriber::class;
    $method = 'bulkDoesNotExist';
    $actionClass = '\MailPoet\Some\Non\Existent\Class';
    try {
      $this->bulkActionFactory->registerAction($modelClass, $method, $actionClass);
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->stringContainsString('has no method');
    }
  }

  public function testItReturnsModelClassByDefault() {
    $modelClass = Subscriber::class;
    $method = 'bulkTrash';
    $resultingClass = $this->bulkActionFactory->getActionClass($modelClass, $method);
    expect($resultingClass)->equals($modelClass);
  }

  public function testItThrowsIfANonExistentModelMethodIsProvided() {
    $modelClass = Subscriber::class;
    $method = 'bulkDoesNotExist';
    try {
      $this->bulkActionFactory->getActionClass($modelClass, $method);
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->stringContainsString('has no method');
    }
  }
}
