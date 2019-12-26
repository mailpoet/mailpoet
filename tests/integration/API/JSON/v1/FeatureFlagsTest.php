<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\FeatureFlags;
use MailPoet\Entities\FeatureFlagEntity;
use MailPoet\Features\FeatureFlagsController;
use MailPoet\Features\FeatureFlagsRepository;
use MailPoet\Features\FeaturesController;
use MailPoet\Models\FeatureFlag;

class FeatureFlagsTest extends \MailPoetTest {

  /** @var FeatureFlagsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->di_container->get(FeatureFlagsRepository::class);
    $table_name = $this->entity_manager->getClassMetadata(FeatureFlagEntity::class)->getTableName();
    $this->entity_manager->getConnection()->executeUpdate("TRUNCATE $table_name");
  }

  public function testItReturnsDefaults() {
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

  public function testItReturnsDatabaseValue() {
    $this->repository->createOrUpdate([
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

  public function testItSetsDatabaseValue() {
    $endpoint = $this->createEndpointWithFeatureDefaults([
      'feature-a' => true,
    ]);

    $endpoint->set([
      'feature-a' => false,
    ]);

    $this->entity_manager->clear();
    $features = $this->repository->findBy(['name' => 'feature-a']);
    expect($features)->count(1);
    expect($features[0]->getName())->equals('feature-a');
    expect($features[0]->getValue())->equals(false);
  }


  public function testItUpdatesDatabaseValue() {
    $this->repository->createOrUpdate([
      'name' => 'feature-a',
      'value' => false,
    ]);

    $endpoint = $this->createEndpointWithFeatureDefaults([
      'feature-a' => true,
    ]);

    $endpoint->set([
      'feature-a' => true,
    ]);

    $this->entity_manager->clear();
    $features = $this->repository->findBy(['name' => 'feature-a']);
    expect(count($features))->equals(1);
    expect($features[0]->getName())->equals('feature-a');
    expect($features[0]->getValue())->equals(true);
  }

  public function testItDoesNotReturnUnknownFlag() {
    $this->repository->createOrUpdate([
      'name' => 'feature-unknown',
      'value' => true,
    ]);

    $endpoint = $this->createEndpointWithFeatureDefaults([]);
    expect($endpoint->getAll()->data)->isEmpty();
  }

  public function testItDoesNotSaveUnknownFlag() {
    $endpoint = $this->createEndpointWithFeatureDefaults([]);
    $response = $endpoint->set([
      'feature-unknown' => false,
    ]);

    expect($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $features = $this->repository->findAll();
    expect(count($features))->equals(0);
  }

  /** @return FeatureFlags */
  private function createEndpointWithFeatureDefaults(array $defaults) {
    $features_controller = $this->make(FeaturesController::class, [
      'defaults' => $defaults,
    ]);
    $feature_flags = Stub::make(FeatureFlagsController::class, [
      'features_controller' => $features_controller,
      'feature_flags_repository' => $this->di_container->get(FeatureFlagsRepository::class),
    ]);
    return new FeatureFlags($features_controller, $feature_flags);
  }

}
