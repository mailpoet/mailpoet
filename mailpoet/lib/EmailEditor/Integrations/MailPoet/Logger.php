<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use MailPoet\Logging\LoggerFactory;
use MailPoetVendor\Monolog\Logger as MonologLogger;

/**
 * MailPoet logger adapter for the email editor.
 *
 * This class adapts the a logger instance from the factory to work with the email editor logging interface.
 */
class Logger implements Email_Editor_Logger_Interface {

  private MonologLogger $mailpoetLogger;

  public function __construct() {
    $this->mailpoetLogger = LoggerFactory::getInstance()->getLogger(LoggerFactory::TOPIC_EMAIL_EDITOR);
  }

  /**
   * Adds emergency level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function emergency(string $message, array $context = []): void {
    $this->mailpoetLogger->emergency($message, $context);
  }

  /**
   * Adds alert level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function alert(string $message, array $context = []): void {
    $this->mailpoetLogger->alert($message, $context);
  }

  /**
   * Adds critical level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function critical(string $message, array $context = []): void {
    $this->mailpoetLogger->critical($message, $context);
  }

  /**
   * Adds error level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function error(string $message, array $context = []): void {
    $this->mailpoetLogger->error($message, $context);
  }

  /**
   * Adds warning level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function warning(string $message, array $context = []): void {
    $this->mailpoetLogger->warning($message, $context);
  }

  /**
   * Adds notice level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function notice(string $message, array $context = []): void {
    $this->mailpoetLogger->notice($message, $context);
  }

  /**
   * Adds info level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function info(string $message, array $context = []): void {
    $this->mailpoetLogger->info($message, $context);
  }

  /**
   * Adds debug level log message.
   *
   * @param string $message The log message.
   * @param array $context The log context.
   * @return void
   */
  public function debug(string $message, array $context = []): void {
    $this->mailpoetLogger->debug($message, $context);
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
    /** @phpstan-ignore-next-line PHPStan reports string in level as an error but it's okay */
    $this->mailpoetLogger->log($level, $message, $context);
  }
}
