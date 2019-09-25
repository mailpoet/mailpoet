<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Features\FeatureFlagsRepository;

class Features {

  /** @var FeatureFlagsRepository */
  private $flags;

  function __construct() {
    $this->flags = ContainerWrapper::getInstance(WP_DEBUG)->get(FeatureFlagsRepository::class);
  }

  function withFeatureEnabled($name) {
    $this->flags->createOrUpdate([
      'name' => $name,
      'value' => true,
    ]);
    return $this;
  }

  function withFeatureDisabled($name) {
    $this->flags->createOrUpdate([
      'name' => $name,
      'value' => false,
    ]);
    return $this;
  }
}
