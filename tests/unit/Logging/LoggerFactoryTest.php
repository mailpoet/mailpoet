<?php

namespace MailPoet\Logging;

use MailPoet\Settings\SettingsController;
use MailPoetVendor\Monolog\Handler\AbstractHandler;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class LoggerFactoryTest extends \MailPoetUnitTest {

  /** @var MockObject */
  private $settings;

  /** @var LoggerFactory */
  private $logger_factory;

  function _before() {
    parent::_before();
    $this->settings = $this->createMock(SettingsController::class);
    $this->logger_factory = new LoggerFactory($this->settings);
  }

  public function testItCreatesLogger() {
    $logger = $this->logger_factory->getLogger('logger-name');
    expect($logger)->isInstanceOf(\MailPoetVendor\Monolog\Logger::class);
  }

  public function testItReturnsInstance() {
    $logger1 = $this->logger_factory->getLogger('logger-name');
    $logger2 = $this->logger_factory->getLogger('logger-name');
    expect($logger1)->same($logger2);
  }

  public function testItAttachesProcessors() {
    $logger1 = $this->logger_factory->getLogger('logger-with-processors', true);
    $processors = $logger1->getProcessors();
    expect($processors)->notEmpty();
  }

  public function testItDoesNotAttachProcessors() {
    $logger1 = $this->logger_factory->getLogger('logger-without-processors', false);
    $processors = $logger1->getProcessors();
    expect($processors)->isEmpty();
  }

  public function testItAttachesHandler() {
    $logger1 = $this->logger_factory->getLogger('logger-with-handler');
    $handlers = $logger1->getHandlers();
    expect($handlers)->notEmpty();
    expect($handlers[0])->isInstanceOf(LogHandler::class);
  }

  public function testItSetsDefaultLoggingLevel() {
    $this->settings->expects($this->once())->method('get')->willReturn(null);
    $logger1 = $this->logger_factory->getLogger('logger-with-handler');
    $handlers = $logger1->getHandlers();
    assert($handlers[0] instanceof AbstractHandler);
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::ERROR);
  }

  public function testItSetsLoggingLevelForNothing() {
    $this->settings->expects($this->once())->method('get')->willReturn('nothing');
    $logger1 = $this->logger_factory->getLogger('logger-for-nothing');
    $handlers = $logger1->getHandlers();
    assert($handlers[0] instanceof AbstractHandler);
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::EMERGENCY);
  }

  public function testItSetsLoggingLevelForErrors() {
    $this->settings->expects($this->once())->method('get')->willReturn('errors');
    $logger1 = $this->logger_factory->getLogger('logger-for-errors');
    $handlers = $logger1->getHandlers();
    assert($handlers[0] instanceof AbstractHandler);
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::ERROR);
  }

  public function testItSetsLoggingLevelForEverything() {
    $this->settings->expects($this->once())->method('get')->willReturn('everything');
    $logger1 = $this->logger_factory->getLogger('logger-for-everything');
    $handlers = $logger1->getHandlers();
    assert($handlers[0] instanceof AbstractHandler);
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::DEBUG);
  }
}
