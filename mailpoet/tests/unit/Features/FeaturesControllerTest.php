<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\Entities\FeatureFlagEntity;
use MailPoet\Features\FeatureFlagsRepository;
use MailPoet\Features\FeaturesController;

class FeaturesControllerTest extends \MailPoetUnitTest {
  public function testItWorksWithDefaults() {
    $repository = $this->makeEmpty(
      FeatureFlagsRepository::class,
      [
        'findAll' => [],
      ]
    );
    $controller = $this->construct(
      FeaturesController::class,
      [$repository],
      [
        'defaults' => [
          'feature-a' => true,
          'feature-b' => false,
        ],
      ]
    );

    expect($controller->isSupported('feature-a'))->equals(true);
    expect($controller->isSupported('feature-b'))->equals(false);
    expect($controller->getAllFlags())->equals([
      'feature-a' => true,
      'feature-b' => false,
    ]);
  }

  public function testItWorksWithDatabaseValues() {
    $repository = $this->makeEmpty(
      FeatureFlagsRepository::class,
      [
        'findAll' => [new FeatureFlagEntity('feature-a', false)],
      ]
    );

    $controller = $this->construct(FeaturesController::class, [$repository], [
      'defaults' => [
        'feature-a' => true,
      ],
    ]);

    expect($controller->isSupported('feature-a'))->equals(false);
    expect($controller->getAllFlags())->equals([
      'feature-a' => false,
    ]);
  }

  public function testItDoesNotReturnUnknownFlag() {
    $repository = $this->makeEmpty(
      FeatureFlagsRepository::class,
      [
        'findAll' => [new FeatureFlagEntity('feature-unknown', true)],
      ]
    );

    $controller = $this->construct(FeaturesController::class, [$repository], [
      'defaults' => [],
    ]);

    try {
      $controller->isSupported('feature-unknown');
    } catch (\RuntimeException $e) {
      expect($e->getMessage())->equals("Unknown feature 'feature-unknown'");
    }
    expect($controller->getAllFlags())->isEmpty();
  }
}
