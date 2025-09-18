<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Monolog\Logger as MonologLogger;

/**
 * MailPoet logger adapter for the email editor.
 *
 * This class adapts the a logger instance from the factory to work with the email editor logging interface.
 */
class Logger implements Email_Editor_Logger_Interface {

  private MonologLogger $mailpoetLogger;
  private SettingsController $settings;

  public function __construct() {
    $this->mailpoetLogger = LoggerFactory::getInstance()->getLogger(LoggerFactory::TOPIC_EMAIL_EDITOR);
    $this->settings = SettingsController::getInstance();
  }

  /**
   * Determines if a log level should be logged based on MailPoet settings and WP_DEBUG.
   *
   * @param int $level The Monolog log level constant
   * @return bool True if the log level should be logged, false otherwise
   */
  private function shouldLogLevel(int $level): bool {
    $mailpoetLogLevel = $this->settings->get('logging', 'errors');
    
    if ($mailpoetLogLevel === 'nothing') {
      return false;
    }

    if ($mailpoetLogLevel === 'errors') {
      return $level >= MonologLogger::ERROR;
    }
    
    if ($mailpoetLogLevel === 'everything') {
      if (defined('WP_DEBUG') && WP_DEBUG) {
        return $level >= MonologLogger::DEBUG;
      }

      // If WP_DEBUG is disabled, log INFO level and above to reduce log entries.
      return $level >= MonologLogger::INFO;
    }
    
    return $level >= MonologLogger::ERROR;
  }

  /**
   * Adds emergency level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function emergency(string $message, array $context = []): void {
    if ($this->shouldLogLevel(MonologLogger::EMERGENCY)) {
      $this->mailpoetLogger->emergency($message, $context);
    }
  }

  /**
   * Adds alert level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function alert(string $message, array $context = []): void {
    if ($this->shouldLogLevel(MonologLogger::ALERT)) {
      $this->mailpoetLogger->alert($message, $context);
    }
  }

  /**
   * Adds critical level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function critical(string $message, array $context = []): void {
    if ($this->shouldLogLevel(MonologLogger::CRITICAL)) {
      $this->mailpoetLogger->critical($message, $context);
    }
  }

  /**
   * Adds error level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function error(string $message, array $context = []): void {
    if ($this->shouldLogLevel(MonologLogger::ERROR)) {
      $this->mailpoetLogger->error($message, $context);
    }
  }

  /**
   * Adds warning level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function warning(string $message, array $context = []): void {
    if ($this->shouldLogLevel(MonologLogger::WARNING)) {
      $this->mailpoetLogger->warning($message, $context);
    }
  }

  /**
   * Adds notice level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function notice(string $message, array $context = []): void {
    if ($this->shouldLogLevel(MonologLogger::NOTICE)) {
      $this->mailpoetLogger->notice($message, $context);
    }
  }

  /**
   * Adds info level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function info(string $message, array $context = []): void {
    if ($this->shouldLogLevel(MonologLogger::INFO)) {
      $this->mailpoetLogger->info($message, $context);
    }
  }

  /**
   * Adds debug level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function debug(string $message, array $context = []): void {
    if ($this->shouldLogLevel(MonologLogger::DEBUG)) {
      $this->mailpoetLogger->debug($message, $context);
    }
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param string $level The log level.
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function log(string $level, string $message, array $context = []): void {
    if (!in_array($level, MonologLogger::getLevels()) && !is_numeric($level)) {
      $level = MonologLogger::DEBUG;
    }
    /** @phpstan-ignore-next-line We check above that it's valid monolog level */
    $monologLevel = MonologLogger::toMonologLevel($level);
    if ($this->shouldLogLevel($monologLevel)) {
      /** @phpstan-ignore-next-line PHPStan reports string in level as an error but it's okay */
      $this->mailpoetLogger->log($level, $message, $context);
    }
  }
}
