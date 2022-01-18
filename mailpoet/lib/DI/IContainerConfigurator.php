<?php

namespace MailPoet\DI;

use MailPoetVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

interface IContainerConfigurator {
  const FREE_CONTAINER_SERVICE_SLUG = 'free_container';
  const PREMIUM_CONTAINER_SERVICE_SLUG = 'premium_container';

  public function configure(ContainerBuilder $container);

  public function getDumpNamespace();

  public function getDumpClassname();
}
