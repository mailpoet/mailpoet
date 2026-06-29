<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\SendingQueue\SendingThrottlingHandler;
use MailPoet\Features\FeatureFlagsController;
use MailPoet\Features\FeaturesController;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;

class SendingThrottlingHandlerTest extends \MailPoetTest {

  /** @var SendingThrottlingHandler */
  private $throttlingHandler;

  /** @var SettingsController */
  private $settings;

  /** @var FeatureFlagsController */
  private $featureFlagsController;

  /** @var FeaturesController */
  private $featuresController;

  public function _before() {
    parent::_before();
    $this->throttlingHandler = $this->diContainer->get(SendingThrottlingHandler::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->featureFlagsController = $this->diContainer->get(FeatureFlagsController::class);
    $this->featuresController = $this->diContainer->get(FeaturesController::class);
  }

  public function _after() {
    $this->disableMssTemplatedSending();
    parent::_after();
  }

  public function testItReturnsDefaultBatchSize(): void {
    $batchSize = $this->throttlingHandler->getBatchSize();
    verify($batchSize)->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  public function testItIgnoresServerAdvertisedMaxWhenMssTemplatedSendingIsDisabled(): void {
    $this->settings->set(API::SETTING_KEY_MAX_MESSAGES_PER_REQUEST, 5);
    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  public function testItCapsBatchSizeToServerAdvertisedMaxWhenMssTemplatedSendingIsEnabled(): void {
    $this->enableMssTemplatedSending();

    $this->settings->set(API::SETTING_KEY_MAX_MESSAGES_PER_REQUEST, 5);
    verify($this->throttlingHandler->getBatchSize())->equals(5);
  }

  public function testItIgnoresServerMaxWhenHigherThanLocalBatchSize(): void {
    $this->enableMssTemplatedSending();

    $this->settings->set(API::SETTING_KEY_MAX_MESSAGES_PER_REQUEST, SendingThrottlingHandler::BATCH_SIZE + 100);
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

  private function enableMssTemplatedSending(): void {
    $this->featureFlagsController->set(FeaturesController::FEATURE_MSS_TEMPLATED_SENDING, true);
    $this->featuresController->resetCache();
  }

  private function disableMssTemplatedSending(): void {
    $this->featureFlagsController->set(FeaturesController::FEATURE_MSS_TEMPLATED_SENDING, false);
    $this->featuresController->resetCache();
  }
}
