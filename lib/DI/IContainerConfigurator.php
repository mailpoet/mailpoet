<?php

namespace MailPoet\DI;

use MailPoetVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

interface IContainerConfigurator {
  const FREE_CONTAINER_SERVICE_SLUG = 'free_container';
  const PREMIUM_CONTAINER_SERVICE_SLUG = 'premium_container';

  function configure(ContainerBuilder $container);
  function getDumpNamespace();
  function getDumpClassname();
}
