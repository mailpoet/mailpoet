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

    verify($controller->isSupported('feature-a'))->equals(true);
    verify($controller->isSupported('feature-b'))->equals(false);
    verify($controller->getAllFlags())->equals([
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

    verify($controller->isSupported('feature-a'))->equals(false);
    verify($controller->getAllFlags())->equals([
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
      verify($e->getMessage())->equals("Unknown feature 'feature-unknown'");
    }
    verify($controller->getAllFlags())->empty();
  }

  public function testMssMessageCompressionIsDisabledByDefault() {
    $repository = $this->makeEmpty(
      FeatureFlagsRepository::class,
      [
        'findAll' => [],
      ]
    );

    $controller = new FeaturesController($repository);

    verify($controller->isSupported(FeaturesController::FEATURE_MSS_MESSAGE_COMPRESSION))->false();
    verify($controller->isMssMessageCompressionSupported())->false();
  }

  public function testMssMessageCompressionRequiresGzipSupport() {
    $repository = $this->makeEmpty(
      FeatureFlagsRepository::class,
      [
        'findAll' => [new FeatureFlagEntity(FeaturesController::FEATURE_MSS_MESSAGE_COMPRESSION, true)],
      ]
    );

    $controller = $this->getMockBuilder(FeaturesController::class)
      ->setConstructorArgs([$repository])
      ->onlyMethods(['isGzipCompressionAvailable'])
      ->getMock();
    $controller->method('isGzipCompressionAvailable')->willReturn(false);

    verify($controller->isMssMessageCompressionSupported())->false();
  }

  public function testMssMessageCompressionIsSupportedWhenFlagAndGzipAreAvailable() {
    $repository = $this->makeEmpty(
      FeatureFlagsRepository::class,
      [
        'findAll' => [new FeatureFlagEntity(FeaturesController::FEATURE_MSS_MESSAGE_COMPRESSION, true)],
      ]
    );

    $controller = $this->getMockBuilder(FeaturesController::class)
      ->setConstructorArgs([$repository])
      ->onlyMethods(['isGzipCompressionAvailable'])
      ->getMock();
    $controller->method('isGzipCompressionAvailable')->willReturn(true);

    verify($controller->isMssMessageCompressionSupported())->true();
  }
}
