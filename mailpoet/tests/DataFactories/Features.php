<?php declare(strict_types = 1);

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Features\FeatureFlagsRepository;

class Features {

  /** @var FeatureFlagsRepository */
  private $flags;

  public function __construct() {
    $this->flags = ContainerWrapper::getInstance(WP_DEBUG)->get(FeatureFlagsRepository::class);
  }

  public function withFeatureEnabled($name) {
    $this->flags->createOrUpdate([
      'name' => $name,
      'value' => true,
    ]);
    return $this;
  }

  public function withFeatureDisabled($name) {
    $this->flags->createOrUpdate([
      'name' => $name,
      'value' => false,
    ]);
    return $this;
  }
}
