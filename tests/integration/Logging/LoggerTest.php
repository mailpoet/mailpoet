<?php

namespace MailPoet\Logging;

use MailPoet\Settings\SettingsController;

class LoggerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = new SettingsController();
  }

  public function testItCreatesLogger() {
    $logger = LoggerFactory::getLogger('logger-name');
    expect($logger)->isInstanceOf(\MailPoetVendor\Monolog\Logger::class);
  }

  public function testItReturnsInstance() {
    $logger1 = LoggerFactory::getLogger('logger-name');
    $logger2 = LoggerFactory::getLogger('logger-name');
    expect($logger1)->same($logger2);
  }

  public function testItAttachesProcessors() {
    $logger1 = LoggerFactory::getLogger('logger-with-processors', true);
    $processors = $logger1->getProcessors();
    expect($processors)->notEmpty();
  }

  public function testItDoesNotAttachProcessors() {
    define(WP_DEBUG, false);
    $logger1 = LoggerFactory::getLogger('logger-without-processors', false);
    $processors = $logger1->getProcessors();
    expect($processors)->isEmpty();
  }

  public function testItAttachesHandler() {
    $logger1 = LoggerFactory::getLogger('logger-with-handler');
    $handlers = $logger1->getHandlers();
    expect($handlers)->notEmpty();
    expect($handlers[0])->isInstanceOf(LogHandler::class);
  }

  public function testItSetsDefaultLoggingLevel() {
    $this->settings->set('logging', null);
    $logger1 = LoggerFactory::getLogger('logger-with-handler');
    $handlers = $logger1->getHandlers();
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::ERROR);
  }

  public function testItSetsLoggingLevelForNothing() {
    $this->settings->set('logging', 'nothing');
    $logger1 = LoggerFactory::getLogger('logger-for-nothing');
    $handlers = $logger1->getHandlers();
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::EMERGENCY);
  }

  public function testItSetsLoggingLevelForErrors() {
    $this->settings->set('logging', 'errors');
    $logger1 = LoggerFactory::getLogger('logger-for-errors');
    $handlers = $logger1->getHandlers();
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::ERROR);
  }

  public function testItSetsLoggingLevelForEverything() {
    $this->settings->set('logging', 'everything');
    $logger1 = LoggerFactory::getLogger('logger-for-everything');
    $handlers = $logger1->getHandlers();
    expect($handlers[0]->getLevel())->equals(\MailPoetVendor\Monolog\Logger::DEBUG);
  }
}
