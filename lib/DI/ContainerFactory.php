<?php

namespace MailPoet\DI;

use MailPoet\Dependencies\Symfony\Component\DependencyInjection\ContainerBuilder;
use MailPoet\Dependencies\Symfony\Component\DependencyInjection\Dumper\PhpDumper;

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
    $container->autowire(\MailPoet\Config\AccessControl::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\Daemon::class)->setPublic(true);
    $container->autowire(\MailPoet\Cron\DaemonHttpRunner::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\CronDaemon::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\Subscription::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\Track::class)->setPublic(true);
    $container->autowire(\MailPoet\Router\Endpoints\ViewInBrowser::class)->setPublic(true);
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
