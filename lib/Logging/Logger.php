<?php

namespace MailPoet\Logging;

use MailPoet\Dependencies\Monolog\Processor\IntrospectionProcessor;
use MailPoet\Dependencies\Monolog\Processor\MemoryUsageProcessor;
use MailPoet\Dependencies\Monolog\Processor\WebProcessor;
use MailPoet\Models\Setting;

class Logger {

  /** @var \MailPoet\Dependencies\Monolog\Logger[] */
  private static $instance = [];

  /**
   * @param string $name
   * @param bool $forceCreate
   *
   * @return \MailPoet\Dependencies\Monolog\Logger
   */
  public static function getLogger($name = 'MailPoet', $forceCreate = false) {
    if(!isset(self::$instance[$name]) || $forceCreate) {
      self::$instance[$name] = new \MailPoet\Dependencies\Monolog\Logger($name);

      if(WP_DEBUG) {
        // Adds the line/file/class/method from which the log call originated
        self::$instance[$name]->pushProcessor(new IntrospectionProcessor());
        // Adds the current request URI, request method and client IP to a log record
        self::$instance[$name]->pushProcessor(new WebProcessor());
        // Adds the current memory usage to a log record
        self::$instance[$name]->pushProcessor(new MemoryUsageProcessor());
      }

      self::$instance[$name]->pushHandler(new LogHandler(self::getDefaultLogLevel()));
    }
    return self::$instance[$name];
  }

  private static function getDefaultLogLevel() {
    $settings = Setting::getValue('logging', 'errors');
    switch ($settings) {
      case 'everything':
        return \MailPoet\Dependencies\Monolog\Logger::DEBUG;
      case 'nothing':
        return \MailPoet\Dependencies\Monolog\Logger::EMERGENCY;
      default:
        return \MailPoet\Dependencies\Monolog\Logger::ERROR;
    }
  }

}