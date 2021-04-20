<?php

namespace MailPoet\Segments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\WP\Functions as WPFunctions;

class SegmentDependencyValidator {
  private const WOOCOMMERCE_PLUGIN = [
    'id' => 'woocommerce/woocommerce.php',
    'name' => 'WooCommerce',
  ];

  private const WOOCOMMERCE_SUBSCRIPTIONS_PLUGIN = [
    'id' => 'woocommerce-subscriptions/woocommerce-subscriptions.php',
    'name' => 'WooCommerce Subscriptions',
  ];

  private const REQUIRED_PLUGINS_BY_TYPE = [
    DynamicSegmentFilterData::TYPE_WOOCOMMERCE => [
      self::WOOCOMMERCE_PLUGIN,
    ],
    DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION => [
      self::WOOCOMMERCE_SUBSCRIPTIONS_PLUGIN,
      self::WOOCOMMERCE_PLUGIN,
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
    $missingPluginNames = [];
    foreach ($segment->getDynamicFilters() as $dynamicFilter) {
      $missingPlugins = $this->getMissingPluginsByFilter($dynamicFilter);
      if (!$missingPlugins) {
        continue;
      }
      foreach ($missingPlugins as $plugin) {
        $missingPluginNames[] = $plugin['name'];
      }
    }
    return array_unique($missingPluginNames);
  }

  public function getMissingPluginsByFilter(DynamicSegmentFilterEntity $dynamicSegmentFilter): array {
    $config = $this->getRequiredPluginsConfig($dynamicSegmentFilter->getFilterData()->getFilterType() ?? '');
    return $this->getMissingPlugins($config);
  }

  public function canUseDynamicFilterType(string $type): bool {
    $config = $this->getRequiredPluginsConfig($type);
    return empty($this->getMissingPlugins($config));
  }

  private function getRequiredPluginsConfig(string $type): array {
    if (isset(self::REQUIRED_PLUGINS_BY_TYPE[$type])) {
      return self::REQUIRED_PLUGINS_BY_TYPE[$type];
    }
    return [];
  }

  private function getMissingPlugins(array $config): array {
    $missingPlugins = [];
    foreach ($config as $requiredPlugin) {
      if (isset($requiredPlugin['id']) && !$this->wp->isPluginActive($requiredPlugin['id'])) {
        $missingPlugins[] = $requiredPlugin;
      }
    }
    return $missingPlugins;
  }
}
