<?php

namespace MailPoet\DynamicSegments\FreePluginConnectors;

use MailPoet\DynamicSegments\Persistence\Loading\Loader;
use MailPoet\DynamicSegments\Persistence\Loading\SubscribersCount;

class AddToSubscribersFilters {

  /** @var Loader */
  private $loader;

  /** @var SubscribersCount */
  private $subscribersCountLoader;

  public function __construct(Loader $loader, SubscribersCount $subscribersCountLoader) {
    $this->loader = $loader;
    $this->subscribersCountLoader = $subscribersCountLoader;
  }

  /**
   * @param array $segmentFilters
   *
   * @return array
   */
  public function add(array $segmentFilters) {
    $dynamicSegments = $this->getListings();
    return $this->sort(array_merge($segmentFilters, $dynamicSegments));
  }

  private function getListings() {
    $dynamicSegments = $this->loader->load();
    return $this->buildResult($dynamicSegments);
  }

  private function buildResult($dynamicSegments) {
    $result = [];
    foreach ($dynamicSegments as $dynamicSegment) {
      $result[] = [
        'value' => $dynamicSegment->id,
        'label' => sprintf(
          '%s (%s)',
          $dynamicSegment->name,
          number_format($this->subscribersCountLoader->getSubscribersCount($dynamicSegment))
        ),
      ];
    }
    return $result;
  }

  private function sort($segmentFilters) {
    $specialSegmentFilters = [];
    $segments = [];
    foreach ($segmentFilters as $segmentFilter) {
      if (is_numeric($segmentFilter['value'])) {
        $segments[] = $segmentFilter;
      } else {
        $specialSegmentFilters[] = $segmentFilter;
      }
    }
    usort($segments, function ($a, $b) {
      return strcasecmp($a["label"], $b["label"]);
    });
    return array_merge($specialSegmentFilters, $segments);
  }

}
