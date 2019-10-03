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
   * @param array $segment_filters
   *
   * @return array
   */
  function add(array $segment_filters) {
    $dynamic_segments = $this->getListings();
    return $this->sort(array_merge($segment_filters, $dynamic_segments));
  }

  private function getListings() {
    $dynamic_segments = $this->loader->load();
    return $this->buildResult($dynamic_segments);
  }

  private function buildResult($dynamic_segments) {
    $result = [];
    foreach ($dynamic_segments as $dynamic_segment) {
      $result[] = [
        'value' => $dynamic_segment->id,
        'label' => sprintf(
          '%s (%s)',
          $dynamic_segment->name,
          number_format($this->subscribersCountLoader->getSubscribersCount($dynamic_segment))
        ),
      ];
    }
    return $result;
  }

  private function sort($segment_filters) {
    $special_segment_filters = [];
    $segments = [];
    foreach ($segment_filters as $segment_filter) {
      if (is_numeric($segment_filter['value'])) {
        $segments[] = $segment_filter;
      } else {
        $special_segment_filters[] = $segment_filter;
      }
    }
    usort($segments, function ($a, $b) {
      return strcasecmp($a["label"], $b["label"]);
    });
    return array_merge($special_segment_filters, $segments);
  }

}
