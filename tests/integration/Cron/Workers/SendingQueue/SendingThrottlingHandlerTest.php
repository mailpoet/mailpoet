<?php

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
    expect($throttlingSettings['success_in_row'])->equals(1);
  }
}
