<?php

namespace MailPoet\Features;

use MailPoet\Models\FeatureFlag;

class FeaturesController {

  // Define features below in the following form:
  //   const FEATURE_NAME_OF_FEATURE = 'name-of-feature';

  // Define feature defaults in the array below in the following form:
  //   self::FEATURE_NAME_OF_FEATURE => true,
  private $defaults = [
  ];

  /** @var array */
  private $flags;

  /** @return bool */
  function isSupported($feature) {
    if (!$this->exists($feature)) {
      throw new \RuntimeException("Unknown feature '$feature'");
    }
    $this->ensureFlagsLoaded();
    return $this->flags[$feature];
  }

  /** @return bool */
  function exists($feature) {
    return array_key_exists($feature, $this->defaults);
  }

  /** @return array */
  function getDefaults() {
    return $this->defaults;
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
    foreach ($this->defaults as $name => $default) {
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
