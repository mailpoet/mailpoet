<?php

namespace MailPoet\Features;

use function MailPoet\Util\array_column;

class FeatureFlagsController {

  /** @var FeaturesController */
  private $features_controller;

  /** @var FeatureFlagsRepository */
  private $feature_flags_repository;

  function __construct(FeaturesController $features_controller, FeatureFlagsRepository $feature_flags_repository) {
    $this->features_controller = $features_controller;
    $this->feature_flags_repository = $feature_flags_repository;
  }

  function set($name, $value) {
    if (!$this->features_controller->exists($name)) {
      throw new \RuntimeException("Feature '$name' does not exist'");
    }

    $this->feature_flags_repository->createOrUpdate(['name' => $name, 'value' => $value]);
  }

  function getAll() {
    $flags = FeatureFlag::findArray();
    $flagsMap = array_combine(array_column($flags, 'name'), $flags);

    $output = [];
    foreach ($this->features_controller->getDefaults() as $name => $default) {
      $output[] = [
        'name' => $name,
        'value' => isset($flagsMap[$name]) ? (bool)$flagsMap[$name]['value'] : $default,
        'default' => $default,
      ];
    }
    return $output;
  }
}
