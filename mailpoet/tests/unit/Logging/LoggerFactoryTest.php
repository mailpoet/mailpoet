<?php declare(strict_types = 1);

namespace MailPoet\Logging;

use MailPoet\Doctrine\EntityManagerFactory;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Doctrine\ORM\EntityManager;
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
    $entityManager = $this->createMock(EntityManager::class);
    $entityManagerFactory = $this->createMock(EntityManagerFactory::class);
    $this->loggerFactory = new LoggerFactory($repository, $entityManager, $entityManagerFactory, $this->settings);
  }

  public function testItCreatesLogger() {
    $logger = $this->loggerFactory->getLogger('logger-name');
    expect($logger)->isInstanceOf(\MailPoetVendor\Monolog\Logger::class);
  }

  public function testItReturnsInstance() {
    $logger1 = $this->loggerFactory->getLogger('logger-name');
    $logger2 = $this->loggerFactory->getLogger('logger-name');
    expect($logger1)->same($logger2);
  }

  public function testItAttachesProcessors() {
    $logger1 = $this->loggerFactory->getLogger('logger-with-processors', true);
    $processors = $logger1->getProcessors();
    expect(count($processors))->greaterThan(1);
  }

  public function testItDoesNotAttachProcessors() {
    $logger1 = $this->loggerFactory->getLogger('logger-without-processors', false);
    $processors = $logger1->getProcessors();
    expect($processors)->count(1);
  }

  public function testItAttachesHandler() {
    $logger1 = $this->loggerFactory->getLogger('logger-with-handler');
    $handlers = $logger1->getHandlers();
    expect($handlers)->notEmpty();
    expect($handlers[0])->isInstanceOf(LogHandler::class);
  }

  public function testItSetsDefaultLoggingLevel() {
    $this->settings->expects($this->once())->method('get')->willReturn(null);
    $logger1 = $this->loggerFactory->getLogger('logger-with-handler');
    $handlers = $logger1->getHandlers();
    $this->assertInstanceOf(AbstractHandler::class, $handlers[0]);
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::ERROR);
  }

  public function testItSetsLoggingLevelForNothing() {
    $this->settings->expects($this->once())->method('get')->willReturn('nothing');
    $logger1 = $this->loggerFactory->getLogger('logger-for-nothing');
    $handlers = $logger1->getHandlers();
    $this->assertInstanceOf(AbstractHandler::class, $handlers[0]);
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::EMERGENCY);
  }

  public function testItSetsLoggingLevelForErrors() {
    $this->settings->expects($this->once())->method('get')->willReturn('errors');
    $logger1 = $this->loggerFactory->getLogger('logger-for-errors');
    $handlers = $logger1->getHandlers();
    $this->assertInstanceOf(AbstractHandler::class, $handlers[0]);
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::ERROR);
  }

  public function testItSetsLoggingLevelForEverything() {
    $this->settings->expects($this->once())->method('get')->willReturn('everything');
    $logger1 = $this->loggerFactory->getLogger('logger-for-everything');
    $handlers = $logger1->getHandlers();
    $this->assertInstanceOf(AbstractHandler::class, $handlers[0]);
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::DEBUG);
  }
}
