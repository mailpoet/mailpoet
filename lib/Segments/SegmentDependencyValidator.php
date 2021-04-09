<?php

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\WP\Functions as WPFunctions;

class SegmentDependencyValidator {
  private const REQUIRED_PLUGINS_BY_TYPE = [
    DynamicSegmentFilterData::TYPE_WOOCOMMERCE => [
      'id' => 'woocommerce/woocommerce.php',
      'name' => 'WooCommerce',
    ],
    DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION => [
      'id' => 'woocommerce-subscriptions/woocommerce-subscriptions.php',
      'name' => 'WooCommerce Subscriptions',
    ],
  ];

  /** @var WPFunctions */
  private $wp;

  public function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  /**
   * @return string[]
   */
  public function getMissingPluginsBySegment(SegmentEntity $segment): array {
    $missingPlugins = [];
    foreach ($segment->getDynamicFilters() as $dynamicFilter) {
      $missingPlugin = $this->getMissingPluginByFilter($dynamicFilter);
      if (!$missingPlugin) {
        continue;
      }
      $missingPlugins[] = $missingPlugin['name'];
    }
    return $missingPlugins;
  }

  public function getMissingPluginByFilter(DynamicSegmentFilterEntity $dynamicSegmentFilter): ?array {
    $requiredPlugin = $this->getRequiredPluginConfig($dynamicSegmentFilter->getFilterData()->getFilterType() ?? '');
    if (isset($requiredPlugin['id']) && !$this->wp->isPluginActive($requiredPlugin['id'])) {
      return $requiredPlugin;
    }
    return null;
  }

  public function canUseDynamicFilterType(string $type) {
    $requiredPlugin = $this->getRequiredPluginConfig($type);
    return isset($requiredPlugin['id']) && $this->wp->isPluginActive($requiredPlugin['id']);
  }

  private function getRequiredPluginConfig(string $type): ?array {
    if (isset(self::REQUIRED_PLUGINS_BY_TYPE[$type])) {
      return self::REQUIRED_PLUGINS_BY_TYPE[$type];
    }

    return null;
  }
}
