<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Settings\SettingsController;
use MailPoetVendor\Monolog\Handler\AbstractHandler;
use PHPUnit\Framework\MockObject\MockObject;

class LoggerFactoryTest extends \MailPoetUnitTest {

  /** @var MockObject */
  private $settings;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function _before() {
    parent::_before();
    $this->settings = $this->createMock(SettingsController::class);
    $repository = $this->createMock(LogRepository::class);
    $this->loggerFactory = new LoggerFactory($repository, $this->settings);
  }

  public function testItCreatesLogger() {
    $logger = $this->loggerFactory->getLogger('logger-name');
    verify($logger)->instanceOf(\MailPoetVendor\Monolog\Logger::class);
  }

  public function testItReturnsInstance() {
    $logger1 = $this->loggerFactory->getLogger('logger-name');
    $logger2 = $this->loggerFactory->getLogger('logger-name');
    verify($logger1)->same($logger2);
  }

  public function testItAttachesProcessors() {
    $logger1 = $this->loggerFactory->getLogger('logger-with-processors', true);
    $processors = $logger1->getProcessors();
    verify(count($processors))->greaterThan(1);
  }

  public function testItSkipsOptionalProcessors() {
    $logger1 = $this->loggerFactory->getLogger('logger-without-processors', false);
    $processors = $logger1->getProcessors();
    verify($processors)->arrayCount(1);
  }

  public function testItAttachesHandler() {
    $logger1 = $this->loggerFactory->getLogger('logger-with-handler');
    $handlers = $logger1->getHandlers();
    verify($handlers)->notEmpty();
    verify($handlers[0])->instanceOf(LogHandler::class);
  }

  public function testItSetsDefaultLoggingLevel() {
    $this->settings->expects($this->once())->method('get')->willReturn(null);
    $logger1 = $this->loggerFactory->getLogger('logger-with-handler');
    $handlers = $logger1->getHandlers();
    $this->assertInstanceOf(AbstractHandler::class, $handlers[0]);
    verify($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::ERROR);
  }

  public function testItSetsLoggingLevelForNothing() {
    $this->settings->expects($this->once())->method('get')->willReturn('nothing');
    $logger1 = $this->loggerFactory->getLogger('logger-for-nothing');
    $handlers = $logger1->getHandlers();
    $this->assertInstanceOf(AbstractHandler::class, $handlers[0]);
    verify($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::EMERGENCY);
  }

  public function testItSetsLoggingLevelForErrors() {
    $this->settings->expects($this->once())->method('get')->willReturn('errors');
    $logger1 = $this->loggerFactory->getLogger('logger-for-errors');
    $handlers = $logger1->getHandlers();
    $this->assertInstanceOf(AbstractHandler::class, $handlers[0]);
    verify($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::ERROR);
  }

  public function testItSetsLoggingLevelForEverything() {
    $this->settings->expects($this->once())->method('get')->willReturn('everything');
    $logger1 = $this->loggerFactory->getLogger('logger-for-everything');
    $handlers = $logger1->getHandlers();
    $this->assertInstanceOf(AbstractHandler::class, $handlers[0]);
    verify($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::DEBUG);
  }
}
