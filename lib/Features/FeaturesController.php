<?php

namespace MailPoet\Features;

use MailPoet\Models\FeatureFlag;

class FeaturesController {

  // Define features below in the following form:
  //   const FEATURE_NAME_OF_FEATURE = 'name-of-feature';
  const FEATURE_DISPLAY_WOOCOMMERCE_REVENUES = 'display-woocommerce-revenues'; // may also have 'display_revenues' setting

  // Define feature defaults in the array below in the following form:
  //   self::FEATURE_NAME_OF_FEATURE => true,
  public static $defaults = [
    self::FEATURE_DISPLAY_WOOCOMMERCE_REVENUES => true,
  ];

  /** @var array */
  private $flags;

  /** @return bool */
  function isSupported($feature) {
    $this->ensureFlagsLoaded();
    if (!array_key_exists($feature, $this->flags)) {
      throw new \RuntimeException("Unknown feature '$feature'");
    }
    return $this->flags[$feature];
  }

  /** @return array */
  function getAllFlags() {
    $this->ensureFlagsLoaded();
    return $this->flags;
  }

  private function ensureFlagsLoaded() {
    if ($this->flags !== null) {
      return;
    }

    $this->flags = [];
    $flagsMap = $this->getValueMap();
    foreach (self::$defaults as $name => $default) {
      $this->flags[$name] = isset($flagsMap[$name]) ? $flagsMap[$name] : $default;
    }
  }

  private function getValueMap() {
    $features = FeatureFlag::selectMany(['name', 'value'])->findMany();
    $featuresMap = [];
    foreach ($features as $feature) {
      $featuresMap[$feature->name] = (bool)$feature->value;
    }
    return $featuresMap;
  }
}
