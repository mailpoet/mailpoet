<?php
namespace MailPoet\Test\API\JSON\v1;

use MailPoet\Features\FeaturesController;
use MailPoet\Models\FeatureFlag;

class FeaturesControllerTest extends \MailPoetTest {
  /** @var array */
  private $defaults_backup;

  function _before() {
    parent::_before();
    FeatureFlag::deleteMany();
    $this->defaults_backup = FeaturesController::$defaults;
  }

  function testItReturnsDefaults() {
    FeaturesController::$defaults = [
      'feature-a' => true,
      'feature-b' => false,
    ];

    $controller = new FeaturesController();
    expect($controller->isSupported('feature-a'))->equals(true);
    expect($controller->isSupported('feature-b'))->equals(false);
    expect($controller->getAllFlags())->equals([
      'feature-a' => true,
      'feature-b' => false,
    ]);
  }

  function testItReturnsDatabaseValue() {
    FeaturesController::$defaults = [
      'feature-a' => true,
    ];

    FeatureFlag::createOrUpdate([
      'name' => 'feature-a',
      'value' => false,
    ]);

    $controller = new FeaturesController();
    expect($controller->isSupported('feature-a'))->equals(false);
    expect($controller->getAllFlags())->equals([
      'feature-a' => false,
    ]);
  }

  function testItDoesNotReturnUnknownFlag() {
    FeaturesController::$defaults = [];

    FeatureFlag::createOrUpdate([
      'name' => 'feature-unknown',
      'value' => true,
    ]);

    $controller = new FeaturesController();
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
    FeaturesController::$defaults = $this->defaults_backup;
  }
}
