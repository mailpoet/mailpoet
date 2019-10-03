<?php

namespace MailPoet\Premium\DynamicSegments\FreePluginConnectors;

use MailPoet\Premium\DynamicSegments\Persistence\Loading\Loader;
use MailPoet\Premium\DynamicSegments\Persistence\Loading\SubscribersCount;

class AddToNewslettersSegments {

  /** @var  Loader */
  private $loader;

  /** @var \MailPoet\Premium\DynamicSegments\Persistence\Loading\SubscribersCount */
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
  function add(array $initial_segments) {
    $dynamic_segments = $this->getListings();
    return array_merge($initial_segments, $dynamic_segments);
  }

  private function getListings() {
    $dynamic_segments = $this->loader->load();
    return $this->buildResult($dynamic_segments);
  }

  private function buildResult($dynamic_segments) {
    $result = [];
    foreach ($dynamic_segments as $dynamic_segment) {
      $result[] = [
        'id' => $dynamic_segment->id,
        'name' => $dynamic_segment->name,
        'subscribers' => $this->subscribersCountLoader->getSubscribersCount($dynamic_segment),
        'deleted_at' => $dynamic_segment->deleted_at,
      ];
    }
    return $result;
  }
}
