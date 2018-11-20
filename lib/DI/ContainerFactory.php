<?php

namespace MailPoet\DI;

use MailPoetVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use MailPoetVendor\Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use MailPoetVendor\Symfony\Component\DependencyInjection\Reference;

class ContainerFactory {

  /** @var ContainerBuilder */
  private $container;

  /** @var string */
  private $dump_file = 'CachedContainer.php';

  /** @var string */
  private $dump_class = 'CachedContainer';

  /** @var bool */
  private $debug;

  /**
   * ContainerFactory constructor.
   * @param bool $debug
   */
  public function __construct($debug = false) {
    $this->debug = $debug;
  }

  function getContainer() {
    if($this->container) {
      return $this->container;
    }

    $dump_file = __DIR__ . '/' . $this->dump_file;
    if(!$this->debug && file_exists($dump_file)) {
      require_once $dump_file;
      $this->container = new $this->dump_class();
    } else {
      $this->container = $this->createContainer();
      $this->container->compile();
    }

    return $this->container;
  }

  function createContainer() {
    $container = new ContainerBuilder();
    // API
    $container->autowire(\MailPoet\API\MP\v1\API::class)->setPublic(true);
    $container->register(\MailPoet\API\JSON\API::class)
      ->addArgument(new Reference('service_container'))
      ->setAutowired(true)
      ->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\AutomatedLatestContent::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\CustomFields::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Forms::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\ImportExport::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Mailer::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\MP2Migrator::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Newsletters::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\NewsletterTemplates::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Segments::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\SendingQueue::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Services::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Settings::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Setup::class)->setPublic(true);
    $container->autowire(\MailPoet\API\JSON\v1\Subscribers::class)->setPublic(true);
    // Config
    $container->autowire(\MailPoet\Config\AccessControl::class)->setPublic(true);
    // Cron
    $container->autowire(\MailPoet\Cron\Daemon::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\DaemonHttpRunner::class)->setPublic(true);
    // Router
    $container->autowire(\MailPoet\Router\Endpoints\CronDaemon::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\Subscription::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\Track::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\ViewInBrowser::class)->setPublic(true);
    // Subscribers
    $container->autowire(\MailPoet\Subscribers\NewSubscriberNotificationMailer::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\ConfirmationEmailMailer::class)->setPublic(true);
    $container->autowire(\MailPoet\Subscribers\RequiredCustomFieldValidator::class)->setPublic(true);
    // Newsletter
    $container->autowire(\MailPoet\Newsletter\AutomatedLatestContent::class)->setPublic(true);
    return $container;
  }

  function dumpContainer() {
    $container = $this->createContainer();
    $container->compile();
    $dumper = new PhpDumper($container);
    file_put_contents(
      __DIR__ . '/' . $this->dump_file,
      $dumper->dump([
        'class' => $this->dump_class
      ])
    );
  }
}
