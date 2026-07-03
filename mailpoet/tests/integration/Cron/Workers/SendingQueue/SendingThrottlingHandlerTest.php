<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\SendingQueue\SendingThrottlingHandler;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;

class SendingThrottlingHandlerTest extends \MailPoetTest {

  /** @var SendingThrottlingHandler */
  private $throttlingHandler;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->throttlingHandler = $this->diContainer->get(SendingThrottlingHandler::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->throttlingHandler->setUseTemplatedSending(false);
  }

  public function testItReturnsDefaultBatchSize(): void {
    $batchSize = $this->throttlingHandler->getBatchSize();
    verify($batchSize)->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  public function testItIgnoresServerAdvertisedMaxWhenNotUsingTemplatedSending(): void {
    $this->settings->set(API::SETTING_KEY_MAX_MESSAGES_PER_REQUEST, 5);
    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  public function testItUsesTemplatedBatchSizeWhenTemplatedSendingIsUsed(): void {
    $this->throttlingHandler->setUseTemplatedSending(true);
    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::TEMPLATED_BATCH_SIZE);
  }

  public function testItCapsTemplatedBatchSizeToServerAdvertisedMax(): void {
    $this->throttlingHandler->setUseTemplatedSending(true);
    $this->settings->set(API::SETTING_KEY_MAX_MESSAGES_PER_REQUEST, 500);
    verify($this->throttlingHandler->getBatchSize())->equals(500);
  }

  public function testItIgnoresServerMaxHigherThanTemplatedBatchSize(): void {
    $this->throttlingHandler->setUseTemplatedSending(true);
    $this->settings->set(API::SETTING_KEY_MAX_MESSAGES_PER_REQUEST, SendingThrottlingHandler::TEMPLATED_BATCH_SIZE + 100);
    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::TEMPLATED_BATCH_SIZE);
  }

  public function testItThrottlesBatchSizeToHalf(): void {
    $batchSize = $this->throttlingHandler->getBatchSize();
    verify($batchSize)->equals(SendingThrottlingHandler::BATCH_SIZE);
    verify($this->throttlingHandler->throttleBatchSize())->equals($batchSize / 2);
  }

  public function testItThrottlesTemplatedBatchSizeToHalf(): void {
    $this->throttlingHandler->setUseTemplatedSending(true);
    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::TEMPLATED_BATCH_SIZE);
    verify($this->throttlingHandler->throttleBatchSize())->equals(SendingThrottlingHandler::TEMPLATED_BATCH_SIZE / 2);
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

  public function testItKeepsTemplatedAndStandardThrottlingStateSeparate(): void {
    // A templated send throttles its batch size down.
    $this->throttlingHandler->setUseTemplatedSending(true);
    verify($this->throttlingHandler->throttleBatchSize())->equals(SendingThrottlingHandler::TEMPLATED_BATCH_SIZE / 2);

    // A standard send throttles down and then scales all the way back to its
    // max, which clears its own throttling state.
    $this->throttlingHandler->setUseTemplatedSending(false);
    $this->throttlingHandler->throttleBatchSize();
    for ($i = 1; $i <= SendingThrottlingHandler::SUCCESS_THRESHOLD_TO_INCREASE; $i++) {
      $this->throttlingHandler->processSuccess();
    }
    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE);

    // The templated throttle-down must survive the standard send.
    $this->throttlingHandler->setUseTemplatedSending(true);
    verify($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::TEMPLATED_BATCH_SIZE / 2);
  }
}
