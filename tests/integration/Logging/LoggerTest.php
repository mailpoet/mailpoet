<?php

namespace MailPoet\Logging;

use MailPoet\Models\Setting;

class LoggerTest extends \MailPoetTest {

  public function testItCreatesLogger() {
    $logger = Logger::getLogger('logger-name');
    expect($logger)->isInstanceOf(\MailPoetVendor\Monolog\Logger::class);
  }

  public function testItReturnsInstance() {
    $logger1 = Logger::getLogger('logger-name');
    $logger2 = Logger::getLogger('logger-name');
    expect($logger1)->same($logger2);
  }

  public function testItAttachesProcessors() {
    $logger1 = Logger::getLogger('logger-with-processors', true);
    $processors = $logger1->getProcessors();
    expect($processors)->notEmpty();
  }

  public function testItDoesNotAttachProcessors() {
    define(WP_DEBUG, false);
    $logger1 = Logger::getLogger('logger-without-processors', false);
    $processors = $logger1->getProcessors();
    expect($processors)->isEmpty();
  }

  public function testItAttachesHandler() {
    $logger1 = Logger::getLogger('logger-with-handler');
    $handlers = $logger1->getHandlers();
    expect($handlers)->notEmpty();
    expect($handlers[0])->isInstanceOf(LogHandler::class);
  }

  public function testItSetsDefaultLoggingLevel() {
    Setting::setValue('logging', null);
    $logger1 = Logger::getLogger('logger-with-handler');
    $handlers = $logger1->getHandlers();
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::ERROR);
  }

  public function testItSetsLoggingLevelForNothing() {
    Setting::setValue('logging', 'nothing');
    $logger1 = Logger::getLogger('logger-for-nothing');
    $handlers = $logger1->getHandlers();
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::EMERGENCY);
  }

  public function testItSetsLoggingLevelForErrors() {
    Setting::setValue('logging', 'errors');
    $logger1 = Logger::getLogger('logger-for-errors');
    $handlers = $logger1->getHandlers();
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::ERROR);
  }

  public function testItSetsLoggingLevelForEverything() {
    Setting::setValue('logging', 'everything');
    $logger1 = Logger::getLogger('logger-for-everything');
    $handlers = $logger1->getHandlers();
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::DEBUG);
  }

}