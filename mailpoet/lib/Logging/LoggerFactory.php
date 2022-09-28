<?php

namespace MailPoet\Logging;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Doctrine\EntityManagerFactory;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Monolog\Processor\IntrospectionProcessor;
use MailPoetVendor\Monolog\Processor\MemoryUsageProcessor;
use MailPoetVendor\Monolog\Processor\WebProcessor;

/**
 * Usage:
 * $logger = Logger::getLogger('logger name');
 * $logger->debug('This is a debug message');
 * $logger->info('This is an info');
 * $logger->warning('This is a warning');
 * $logger->error('This is an error message');
 *
 * By default only errors are saved but can be changed in settings to save everything or nothing
 *
 * Name is anything which will be found in the log table.
 *   We can use it for separating different messages like: 'cron', 'rendering', 'export', ...
 *
 * If WP_DEBUG is true additional information will be added to every log message.
 */
class LoggerFactory {
  const TOPIC_NEWSLETTERS = 'newsletters';
  const TOPIC_POST_NOTIFICATIONS = 'post-notifications';
  const TOPIC_MSS = 'mss';
  const TOPIC_BRIDGE = 'bridge-api';
  const TOPIC_SENDING = 'sending';
  const TOPIC_CRON = 'cron';

  /** @var LoggerFactory */
  private static $instance;

  /** @var \MailPoetVendor\Monolog\Logger[] */
  private $loggerInstances = [];

  /** @var SettingsController */
  private $settings;

  /** @var LogRepository */
  private $logRepository;

  /** @var EntityManager */
  private $entityManager;

  /** @var EntityManagerFactory */
  private $entityManagerFactory;

  public function __construct(
    LogRepository $logRepository,
    EntityManager $entityManager,
    EntityManagerFactory $entityManagerFactory,
    SettingsController $settings
  ) {
    $this->settings = $settings;
    $this->logRepository = $logRepository;
    $this->entityManager = $entityManager;
    $this->entityManagerFactory = $entityManagerFactory;
  }

  /**
   * @param string $name
   * @param bool $attachProcessors
   *
   * @return \MailPoetVendor\Monolog\Logger
   */
  public function getLogger($name = 'MailPoet', $attachProcessors = WP_DEBUG) {
    if (!isset($this->loggerInstances[$name])) {
      $this->loggerInstances[$name] = new \MailPoetVendor\Monolog\Logger($name);

      if ($attachProcessors) {
        // Adds the line/file/class/method from which the log call originated
        $this->loggerInstances[$name]->pushProcessor(new IntrospectionProcessor());
        // Adds the current request URI, request method and client IP to a log record
        $this->loggerInstances[$name]->pushProcessor(new WebProcessor());
        // Adds the current memory usage to a log record
        $this->loggerInstances[$name]->pushProcessor(new MemoryUsageProcessor());
      }

      $this->loggerInstances[$name]->pushHandler(new LogHandler(
        $this->logRepository,
        $this->entityManager,
        $this->entityManagerFactory,
        $this->getDefaultLogLevel()
      ));
    }
    return $this->loggerInstances[$name];
  }

  public static function getInstance() {
    if (!self::$instance instanceof LoggerFactory) {
      self::$instance = new LoggerFactory(
        ContainerWrapper::getInstance()->get(LogRepository::class),
        ContainerWrapper::getInstance()->get(EntityManager::class),
        ContainerWrapper::getInstance()->get(EntityManagerFactory::class),
        SettingsController::getInstance()
      );
    }
    return self::$instance;
  }

  private function getDefaultLogLevel() {
    $logLevel = $this->settings->get('logging', 'errors');
    switch ($logLevel) {
      case 'everything':
        return \MailPoetVendor\Monolog\Logger::DEBUG;
      case 'nothing':
        return \MailPoetVendor\Monolog\Logger::EMERGENCY;
      default:
        return \MailPoetVendor\Monolog\Logger::ERROR;
    }
  }
}
