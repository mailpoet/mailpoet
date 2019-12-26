<?php

namespace MailPoet\Listing;

use MailPoet\Models\Subscriber;

require_once('BulkActionClassStub.php');

class BulkActionFactoryTest extends \MailPoetTest {
  /** @var BulkActionFactory */
  private $bulk_action_factory;

  public function _before() {
    $this->bulk_action_factory = new BulkActionFactory();
  }

  public function testItReturnsCustomActionClass() {
    $model_class = Subscriber::class;
    $method = 'bulkTestAction';
    $action_class = new BulkActionClassStub;
    $this->bulk_action_factory->registerAction($model_class, $method, $action_class);
    $resulting_class = $this->bulk_action_factory->getActionClass($model_class, $method);
    expect($resulting_class)->equals($action_class);
  }

  public function testItThrowsIfANonExistentActionMethodIsBeingRegistered() {
    $model_class = Subscriber::class;
    $method = 'bulkDoesNotExist';
    $action_class = new BulkActionClassStub;
    try {
      $this->bulk_action_factory->registerAction($model_class, $method, $action_class);
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->contains('has no method');
    }
  }

  public function testItThrowsIfANonExistentActionClassIsBeingRegistered() {
    $model_class = Subscriber::class;
    $method = 'bulkDoesNotExist';
    $action_class = '\MailPoet\Some\Non\Existent\Class';
    try {
      $this->bulk_action_factory->registerAction($model_class, $method, $action_class);
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->contains('has no method');
    }
  }

  public function testItReturnsModelClassByDefault() {
    $model_class = Subscriber::class;
    $method = 'bulkTrash';
    $resulting_class = $this->bulk_action_factory->getActionClass($model_class, $method);
    expect($resulting_class)->equals($model_class);
  }

  public function testItThrowsIfANonExistentModelMethodIsProvided() {
    $model_class = Subscriber::class;
    $method = 'bulkDoesNotExist';
    try {
      $this->bulk_action_factory->getActionClass($model_class, $method);
      $this->fail('Exception was not thrown');
    } catch (\Exception $e) {
      expect($e->getMessage())->contains('has no method');
    }
  }
}
