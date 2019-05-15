<?php
namespace MailPoet\Test\API\JSON\v1;

use MailPoet\Features\FeaturesController;
use MailPoet\Models\FeatureFlag;

class FeaturesControllerTest extends \MailPoetTest {

  function _before() {
    parent::_before();
    FeatureFlag::deleteMany();
  }

  function testItWorksWithDefaults() {
    $controller = $this->make(FeaturesController::class, [
      'defaults' => [
        'feature-a' => true,
        'feature-b' => false,
      ],
    ]);

    expect($controller->isSupported('feature-a'))->equals(true);
    expect($controller->isSupported('feature-b'))->equals(false);
    expect($controller->getAllFlags())->equals([
      'feature-a' => true,
      'feature-b' => false,
    ]);
  }

  function testItWorksWithDatabaseValues() {
    FeatureFlag::createOrUpdate([
      'name' => 'feature-a',
      'value' => false,
    ]);

    $controller = $this->make(FeaturesController::class, [
      'defaults' => [
        'feature-a' => true,
      ],
    ]);

    expect($controller->isSupported('feature-a'))->equals(false);
    expect($controller->getAllFlags())->equals([
      'feature-a' => false,
    ]);
  }

  function testItDoesNotReturnUnknownFlag() {
    FeatureFlag::createOrUpdate([
      'name' => 'feature-unknown',
      'value' => true,
    ]);

    $controller = $this->make(FeaturesController::class, [
      'defaults' => [],
    ]);

    try {
      $controller->isSupported('feature-unknown');
    } catch (\RuntimeException $e) {
      expect($e->getMessage())->equals("Unknown feature 'feature-unknown'");
    }
    expect($controller->getAllFlags())->isEmpty();
  }

  function _after() {
    parent::_before();
    FeatureFlag::deleteMany();
  }
}
