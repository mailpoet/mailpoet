<?php declare(strict_types=1);

namespace MailPoet\Services\Bridge;

use MailPoet\Entities\LogEntity;
use MailPoet\Logging\LogRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;

class BridgeApiTest extends \MailPoetTest {

  /** @var API */
  private $api;

  /** @var WPFunctions & MockObject */
  private $wpMock;

  /** @var LogRepository */
  private $logRepository;

  public function _before() {
    parent::_before();
    $this->wpMock = $this->createMock(WPFunctions::class);
    $this->api = new API('test-api-key', $this->wpMock);
    $this->logRepository = $this->diContainer->get(LogRepository::class);
    $this->cleanUp();
  }

  public function testItDoesntLogsWhenPremiumKeyCheckPass() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn('');
    $this->api->checkPremiumKey();
    $logs = $this->logRepository->findAll();
    expect($logs)->count(0);
  }

  public function testItLogsWhenPremiumKeyCheckFails() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(401);
    $this->wpMock
      ->expects($this->once())
      ->method('homeUrl')
      ->willReturn('www.home-example.com');
    $this->api->checkPremiumKey();
    $logs = $this->logRepository->findAll();
    expect($logs)->count(1);
    $errorLog = $logs[0];
    $this->assertInstanceOf(LogEntity::class, $errorLog);
    expect($errorLog->getLevel())->equals(Logger::ERROR);
    expect($errorLog->getMessage())->stringContainsString('www.home-example.com');
    expect($errorLog->getMessage())->stringContainsString('key-validation.failed');
    expect($errorLog->getMessage())->stringContainsString('"key_type":"premium"');
  }

  public function testItDoesntLogsWhenMssKeyCheckPass() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(200);
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveBody')
      ->willReturn('');
    $this->api->checkMSSKey();
    $logs = $this->logRepository->findAll();
    expect($logs)->count(0);
  }

  public function testItLogsWhenMssKeyCheckFails() {
    $this->wpMock
      ->expects($this->once())
      ->method('wpRemoteRetrieveResponseCode')
      ->willReturn(401);
    $this->wpMock
      ->expects($this->once())
      ->method('homeUrl')
      ->willReturn('www.home-example.com');
    $this->api->checkMSSKey();
    $logs = $this->logRepository->findAll();
    expect($logs)->count(1);
    $errorLog = $logs[0];
    $this->assertInstanceOf(LogEntity::class, $errorLog);
    expect($errorLog->getLevel())->equals(Logger::ERROR);
    expect($errorLog->getMessage())->stringContainsString('www.home-example.com');
    expect($errorLog->getMessage())->stringContainsString('key-validation.failed');
    expect($errorLog->getMessage())->stringContainsString('"key_type":"mss"');
  }

  private function cleanUp() {
    $this->truncateEntity(LogEntity::class);
  }
}
