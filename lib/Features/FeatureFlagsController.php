<?php

namespace MailPoet\Features;

use MailPoet\Entities\FeatureFlagEntity;
use MailPoet\Settings\FeatureFlagsRepository;
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

    $feature_flag = $this->feature_flags_repository->findOneBy([
      'name' => $name,
    ]);
    if (!$feature_flag) {
      $feature_flag = new FeatureFlagEntity($name);
      $this->feature_flags_repository->persist($feature_flag);
    }
    $feature_flag->setValue($value);

    try {
      $this->feature_flags_repository->flush();
    } catch (\Exception $e) {
      throw new \RuntimeException("Error when saving feature '$name''");
    }
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
