<?php

namespace MailPoet\Logging;

use MailPoet\Settings\SettingsController;
use MailPoetVendor\Monolog\Processor\IntrospectionProcessor;
use MailPoetVendor\Monolog\Processor\MemoryUsageProcessor;
use MailPoetVendor\Monolog\Processor\WebProcessor;

/**
 * Usage:
 * $logger = Logger::getLogger('logger name');
 * $logger->addDebug('This is a debug message');
 * $logger->addInfo('This is an info');
 * $logger->addWarning('This is a warning');
 * $logger->addError('This is an error message');
 *
 * By default only errors are saved but can be changed in settings to save everything or nothing
 *
 * Name is anything which will be found in the log table.
 *   We can use it for separating different messages like: 'cron', 'rendering', 'export', ...
 *
 * If WP_DEBUG is true additional information will be added to every log message.
 */
class LoggerFactory {

  /** @var LoggerFactory */
  private static $instance;

  /** @var \MailPoetVendor\Monolog\Logger[] */
  private static $logger_instances = [];

  /**
   * @param string $name
   * @param bool $attach_processors
   *
   * @return \MailPoetVendor\Monolog\Logger
   */
  public static function getLogger($name = 'MailPoet', $attach_processors = WP_DEBUG) {
    if (!isset(self::$logger_instances[$name])) {
      self::$logger_instances[$name] = new \MailPoetVendor\Monolog\Logger($name);

      if ($attach_processors) {
        // Adds the line/file/class/method from which the log call originated
        self::$logger_instances[$name]->pushProcessor(new IntrospectionProcessor());
        // Adds the current request URI, request method and client IP to a log record
        self::$logger_instances[$name]->pushProcessor(new WebProcessor());
        // Adds the current memory usage to a log record
        self::$logger_instances[$name]->pushProcessor(new MemoryUsageProcessor());
      }

      self::$logger_instances[$name]->pushHandler(new LogHandler(self::getDefaultLogLevel()));
    }
    return self::$logger_instances[$name];
  }

  static function getInstance() {
    if (!self::$instance instanceof LoggerFactory) {
      self::$instance = new LoggerFactory();
    }
    return self::$instance;
  }

  private static function getDefaultLogLevel() {
    $settings = new SettingsController();
    $log_level = $settings->get('logging', 'errors');
    switch ($log_level) {
      case 'everything':
        return \MailPoetVendor\Monolog\Logger::DEBUG;
      case 'nothing':
        return \MailPoetVendor\Monolog\Logger::EMERGENCY;
      default:
        return \MailPoetVendor\Monolog\Logger::ERROR;
    }
  }

}
