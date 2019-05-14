<?php
namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\FeatureFlags;
use MailPoet\Features\FeatureFlagsController;
use MailPoet\Features\FeaturesController;
use MailPoet\Models\FeatureFlag;

class FeatureFlagsTest extends \MailPoetTest {
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

    $controller = new FeatureFlagsController();
    $endpoint = new FeatureFlags($controller);
    expect($endpoint->getAll()->data)->equals([
      [
        'name' => 'feature-a',
        'value' => true,
        'default' => true,
      ],
      [
        'name' => 'feature-b',
        'value' => false,
        'default' => false,
      ],
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

    $controller = new FeatureFlagsController();
    $endpoint = new FeatureFlags($controller);

    expect($endpoint->getAll()->data)->equals([
      [
        'name' => 'feature-a',
        'value' => false,
        'default' => true,
      ],
    ]);
  }

  function testItSetsDatabaseValue() {
    FeaturesController::$defaults = [
      'feature-a' => true,
    ];

    $controller = new FeatureFlagsController();
    $endpoint = new FeatureFlags($controller);
    $endpoint->set([
      'feature-a' => false,
    ]);

    $features = FeatureFlag::where('name', 'feature-a')->findMany();
    expect(count($features))->equals(1);
    expect($features[0]->name)->equals('feature-a');
    expect($features[0]->value)->equals('0');
  }


  function testItUpdatesDatabaseValue() {
    FeaturesController::$defaults = [
      'feature-a' => true,
    ];

    FeatureFlag::createOrUpdate([
      'name' => 'feature-a',
      'value' => false,
    ]);

    $controller = new FeatureFlagsController();
    $endpoint = new FeatureFlags($controller);
    $endpoint->set([
      'feature-a' => true,
    ]);

    $features = FeatureFlag::where('name', 'feature-a')->findMany();
    expect(count($features))->equals(1);
    expect($features[0]->name)->equals('feature-a');
    expect($features[0]->value)->equals('1');
  }

  function testItDoesNotReturnUnknownFlag() {
    FeaturesController::$defaults = [];

    FeatureFlag::createOrUpdate([
      'name' => 'feature-unknown',
      'value' => true,
    ]);

    $controller = new FeatureFlagsController();
    $endpoint = new FeatureFlags($controller);

    expect($endpoint->getAll()->data)->isEmpty();
  }

  function testItDoesNotSaveUnknownFlag() {
    FeaturesController::$defaults = [];

    $controller = new FeatureFlagsController();
    $endpoint = new FeatureFlags($controller);
    $response = $endpoint->set([
      'feature-unknown' => false,
    ]);

    expect($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $features = FeatureFlag::findMany();
    expect(count($features))->equals(0);
  }

  function _after() {
    parent::_before();
    FeatureFlag::deleteMany();
    FeaturesController::$defaults = $this->defaults_backup;
  }
}
