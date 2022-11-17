<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\SendingQueue\SendingThrottlingHandler;
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
  }

  public function testItReturnsDefaultBatchSize(): void {
    $batchSize = $this->throttlingHandler->getBatchSize();
    expect($batchSize)->equals(SendingThrottlingHandler::BATCH_SIZE);
  }

  public function testItThrottlesBatchSizeToHalf(): void {
    $batchSize = $this->throttlingHandler->getBatchSize();
    expect($batchSize)->equals(SendingThrottlingHandler::BATCH_SIZE);
    expect($this->throttlingHandler->throttleBatchSize())->equals($batchSize / 2);
  }

  public function testItIncreaseSuccessRequestCountInRow(): void {
    $this->throttlingHandler->throttleBatchSize();
    $this->throttlingHandler->processSuccess();
    $throttlingSettings = $this->settings->get(SendingThrottlingHandler::SETTINGS_KEY);
    expect($throttlingSettings['success_count'])->equals(1);
  }

  public function testItSetsBatchSizeMinimumToOne(): void {
    for ($i = 1; $i <= 10; $i++) {
      $this->throttlingHandler->throttleBatchSize();
    }
    expect($this->throttlingHandler->getBatchSize())->equals(1);
  }

  public function testInIncreasesBatchSizeBack(): void {
    $this->settings->set(SendingThrottlingHandler::SETTINGS_KEY, []);
    $this->throttlingHandler->throttleBatchSize();
    expect($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE / 2);
    for ($i = 1; $i <= SendingThrottlingHandler::SUCCESS_THRESHOLD_TO_INCREASE; $i++) {
      $this->throttlingHandler->processSuccess();
    }
    expect($this->throttlingHandler->getBatchSize())->equals(SendingThrottlingHandler::BATCH_SIZE);
  }
}
