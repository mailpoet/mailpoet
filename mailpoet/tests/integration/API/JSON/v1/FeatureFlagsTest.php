<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\FeatureFlags;
use MailPoet\Features\FeatureFlagsController;
use MailPoet\Features\FeatureFlagsRepository;
use MailPoet\Features\FeaturesController;

class FeatureFlagsTest extends \MailPoetTest {

  /** @var FeatureFlagsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(FeatureFlagsRepository::class);
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

    $this->entityManager->clear();
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

    $this->entityManager->clear();
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
    $featuresController = $this->make(FeaturesController::class, [
      'defaults' => $defaults,
    ]);
    $featureFlags = Stub::make(FeatureFlagsController::class, [
      'featuresController' => $featuresController,
      'featureFlagsRepository' => $this->diContainer->get(FeatureFlagsRepository::class),
    ]);
    return new FeatureFlags($featuresController, $featureFlags);
  }
}
