<?php
namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\FeatureFlags;
use MailPoet\Features\FeatureFlagsController;
use MailPoet\Features\FeaturesController;
use MailPoet\Models\FeatureFlag;

class FeatureFlagsTest extends \MailPoetTest {

  function _before() {
    parent::_before();
    FeatureFlag::deleteMany();
  }

  function testItReturnsDefaults() {
    $endpoint = $this->createEndpointWithFeatureDefaults([
      'feature-a' => true,
      'feature-b' => false,
    ]);

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
    FeatureFlag::createOrUpdate([
      'name' => 'feature-a',
      'value' => false,
    ]);

    $endpoint = $this->createEndpointWithFeatureDefaults([
      'feature-a' => true,
    ]);

    expect($endpoint->getAll()->data)->equals([
      [
        'name' => 'feature-a',
        'value' => false,
        'default' => true,
      ],
    ]);
  }

  function testItSetsDatabaseValue() {
    $endpoint = $this->createEndpointWithFeatureDefaults([
      'feature-a' => true,
    ]);

    $endpoint->set([
      'feature-a' => false,
    ]);

    $features = FeatureFlag::where('name', 'feature-a')->findMany();
    expect(count($features))->equals(1);
    expect($features[0]->name)->equals('feature-a');
    expect($features[0]->value)->equals('0');
  }


  function testItUpdatesDatabaseValue() {
    FeatureFlag::createOrUpdate([
      'name' => 'feature-a',
      'value' => false,
    ]);

    $endpoint = $this->createEndpointWithFeatureDefaults([
      'feature-a' => true,
    ]);

    $endpoint->set([
      'feature-a' => true,
    ]);

    $features = FeatureFlag::where('name', 'feature-a')->findMany();
    expect(count($features))->equals(1);
    expect($features[0]->name)->equals('feature-a');
    expect($features[0]->value)->equals('1');
  }

  function testItDoesNotReturnUnknownFlag() {
    FeatureFlag::createOrUpdate([
      'name' => 'feature-unknown',
      'value' => true,
    ]);

    $endpoint = $this->createEndpointWithFeatureDefaults([]);
    expect($endpoint->getAll()->data)->isEmpty();
  }

  function testItDoesNotSaveUnknownFlag() {
    $endpoint = $this->createEndpointWithFeatureDefaults([]);
    $response = $endpoint->set([
      'feature-unknown' => false,
    ]);

    expect($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $features = FeatureFlag::findMany();
    expect(count($features))->equals(0);
  }

  private function createEndpointWithFeatureDefaults(array $defaults) {
    $features_controller = $this->make(FeaturesController::class, [
      'defaults' => $defaults,
    ]);
    $controller = new FeatureFlagsController($features_controller);
    return new FeatureFlags($features_controller, $controller);
  }

  function _after() {
    parent::_before();
    FeatureFlag::deleteMany();
  }
}
