<?php

namespace MailPoet\DI;

use MailPoetVendor\Psr\Container\ContainerInterface;
use MailPoetVendor\Psr\Container\NotFoundExceptionInterface;
use MailPoetVendor\Symfony\Component\DependencyInjection\Container;

class ContainerWrapper implements ContainerInterface {

  /** @var Container */
  private $free_container;

  /** @var Container|null */
  private $premium_container;

  /** @var ContainerWrapper|null */
  private static $instance;

  public function __construct(Container $free_container, Container $premium_container = null) {
    $this->free_container = $free_container;
    $this->premium_container = $premium_container;
  }

  function get($id) {
    try {
      return $this->free_container->get($id);
    } catch (NotFoundExceptionInterface $e) {
      if (!$this->premium_container) {
        throw $e;
      }
      return $this->premium_container->get($id);
    }
  }

  function has($id) {
    return $this->free_container->has($id) || ($this->premium_container && $this->premium_container->has($id));
  }

  /**
   * @return ContainerInterface|null
   */
  function getPremiumContainer() {
    if (!$this->premium_container && class_exists(\MailPoet\Premium\DI\ContainerConfigurator::class)) {
      $this->premium_container = self::createPremiumContainer($this->free_container);
    }
    return $this->premium_container;
  }

  static function getInstance($debug = false) {
    if (self::$instance) {
      return self::$instance;
    }
    $free_container_factory = new ContainerFactory(new ContainerConfigurator(), $debug);
    $free_container = $free_container_factory->getContainer();
    $premium_container = null;
    if (class_exists(\MailPoet\Premium\DI\ContainerConfigurator::class)) {
      $premium_container = self::createPremiumContainer($free_container, $debug);
    }
    self::$instance = new ContainerWrapper($free_container, $premium_container);
    return self::$instance;
  }

  private static function createPremiumContainer(Container $free_container, $debug = false) {
    $premium_container_factory = new ContainerFactory(new \MailPoet\Premium\DI\ContainerConfigurator(), $debug);
    $premium_container = $premium_container_factory->getContainer();
    $premium_container->set(IContainerConfigurator::FREE_CONTAINER_SERVICE_SLUG, $free_container);
    $free_container->set(IContainerConfigurator::PREMIUM_CONTAINER_SERVICE_SLUG, $premium_container);
    return $premium_container;
  }
}
