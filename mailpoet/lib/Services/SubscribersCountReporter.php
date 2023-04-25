<?php declare(strict_types = 1);

namespace MailPoet\Services;

use MailPoet\Util\License\Features\Subscribers;

class SubscribersCountReporter {
  /** @var Bridge */
  private $bridge;

  /** @var Subscribers */
  private $subscribersFeature;

  public function __construct(
    Bridge $bridge,
    Subscribers $subscribersFeature
  ) {
    $this->bridge = $bridge;
    $this->subscribersFeature = $subscribersFeature;
  }

  public function report(string $key): bool {
    $subscribersCount = $this->subscribersFeature->getSubscribersCount();
    return $this->bridge->updateSubscriberCount($key, $subscribersCount);
  }
}
