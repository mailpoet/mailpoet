<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use MailPoet\DynamicSegments\Persistence\Loading\Loader;
use MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount;

class AddToNewslettersSegments {

  /** @var  Loader */
  private $loader;

  /** @var SubscribersCount */
  private $subscribersCountLoader;

  public function __construct(Loader $loader, SubscribersCount $subscribersCountLoader) {
    $this->loader = $loader;
    $this->subscribersCountLoader = $subscribersCountLoader;
  }

  /**
   * @param array $initial_segments
   *
   * @return array
   */
  public function add(array $initialSegments) {
    $dynamicSegments = $this->getListings();
    return array_merge($initialSegments, $dynamicSegments);
  }

  private function getListings() {
    $dynamicSegments = $this->loader->load();
    return $this->buildResult($dynamicSegments);
  }

  private function buildResult($dynamicSegments) {
    $result = [];
    foreach ($dynamicSegments as $dynamicSegment) {
      $result[] = [
        'id' => $dynamicSegment->id,
        'name' => $dynamicSegment->name,
        'subscribers' => $this->subscribersCountLoader->getSubscribersCount($dynamicSegment),
        'deleted_at' => $dynamicSegment->deletedAt,
      ];
    }
    return $result;
  }
}
