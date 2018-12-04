<?php

namespace MailPoet\DI;

use MailPoetVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use MailPoetVendor\Symfony\Component\DependencyInjection\Dumper\PhpDumper;

class ContainerFactory {

  /** @var IContainerConfigurator */
  private $configurator;

  /** @var bool */
  private $debug;

  /**
   * ContainerFactory constructor.
   * @param bool $debug
   */
  public function __construct(IContainerConfigurator $configurator, $debug = false) {
    $this->debug = $debug;
    $this->configurator = $configurator;
  }

  function getContainer() {
    $dump_class = '\\'. $this->configurator->getDumpNamespace() . '\\' . $this->configurator->getDumpClassname();
    if(!$this->debug && class_exists($dump_class)) {
      $container = new $dump_class();
    } else {
      $container = $this->getConfiguredContainer();
      $container->compile();
    }
    return $container;
  }

  function getConfiguredContainer() {
    return $this->configurator->configure(new ContainerBuilder());
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
