<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\SendingQueue\SendingThrottlingHandler;
use MailPoet\Features\FeatureFlagsRepository;
use MailPoet\Features\FeaturesController;
use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;

class SendingThrottlingHandlerTest extends \MailPoetTest {

  /** @var SendingThrottlingHandler */
  private $throttlingHandler;

  /** @var SettingsController */
  private $settings;

  /** @var FeaturesController */
  private $featuresController;

  /** @var FeatureFlagsRepository */
  private $featureFlagsRepository;

  public function _before() {
    parent::_before();
    $this->throttlingHandler = $this->diContainer->get(SendingThrottlingHandler::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->featuresController = $this->diContainer->get(FeaturesController::class);
    $this->featureFlagsRepository = $this->diContainer->get(FeatureFlagsRepository::class);
  }

  public function testItReturnsDefaultBatchSize(): void {
    $batchSize = $this->throttlingHandler->getBatchSize();
    verify($batchSize)->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  public function testItReturnsCompressedMssBatchSizeWhenCompressionIsSupported(): void {
    if (!function_exists('gzencode')) {
      $this->markTestSkipped('Gzip support is not available.');
    }

    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $this->setMssMessageCompressionFeature(true);

    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::COMPRESSED_MSS_BATCH_SIZE);
  }

  public function testItKeepsDefaultBatchSizeForMssWhenCompressionFeatureIsDisabled(): void {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $this->setMssMessageCompressionFeature(false);

    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  public function testItKeepsDefaultBatchSizeForNonMssWhenCompressionFeatureIsEnabled(): void {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_PHPMAIL]);
    $this->setMssMessageCompressionFeature(true);

    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  public function testItClampsStoredBatchSizeToCurrentMaximum(): void {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_PHPMAIL]);
    $this->settings->set(SendingThrottlingHandler::SETTINGS_KEY, ['batch_size' => SendingThrottlingHandler::COMPRESSED_MSS_BATCH_SIZE]);

    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  public function testItThrottlesBatchSizeToHalf(): void {
    $batchSize = $this->throttlingHandler->getBatchSize();
    verify($batchSize)->equals(SendingThrottlingHandler::BATCH_SIZE);
    verify($this->throttlingHandler->throttleBatchSize())->equals($batchSize / 2);
  }

  public function testItIncreaseSuccessRequestCountInRow(): void {
    $this->throttlingHandler->throttleBatchSize();
    $this->throttlingHandler->processSuccess();
    $throttlingSettings = $this->settings->get(SendingThrottlingHandler::SETTINGS_KEY);
    verify($throttlingSettings['success_count'])->equals(1);
  }

  public function testItSetsBatchSizeMinimumToOne(): void {
    for ($i = 1; $i <= 10; $i++) {
      $this->throttlingHandler->throttleBatchSize();
    }
    verify($this->throttlingHandler->getBatchSize())->equals(1);
  }

  public function testInIncreasesBatchSizeBack(): void {
    $this->settings->set(SendingThrottlingHandler::SETTINGS_KEY, []);
    $this->throttlingHandler->throttleBatchSize();
    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE / 2);
    for ($i = 1; $i <= SendingThrottlingHandler::SUCCESS_THRESHOLD_TO_INCREASE; $i++) {
      $this->throttlingHandler->processSuccess();
    }
    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  private function setMssMessageCompressionFeature(bool $value): void {
    $this->featureFlagsRepository->createOrUpdate([
      'name' => FeaturesController::FEATURE_MSS_MESSAGE_COMPRESSION,
      'value' => $value,
    ]);
    $this->featuresController->resetCache();
  }
}
