<?php

namespace MailPoet\Features;

class FeaturesController {

  // Define features below in the following form:
  //   const FEATURE_NAME_OF_FEATURE = 'name-of-feature';
  const NEW_DEFAULT_LIST_NAME = 'new-default-list-name';
  const SEND_WORDPRESS_MAILS_WITH_MP3 = 'send-wordpress-mails-with-mp3';
  const NEW_PREMIUM_PAGE = 'new-premium-page';

  // Define feature defaults in the array below in the following form:
  //   self::FEATURE_NAME_OF_FEATURE => true,
  private $defaults = [
    self::NEW_DEFAULT_LIST_NAME => false,
    self::SEND_WORDPRESS_MAILS_WITH_MP3 => false,
    self::NEW_PREMIUM_PAGE => false,
  ];

  /** @var array */
  private $flags;

  /** @var FeatureFlagsRepository */
  private $feature_flags_repository;

  public function __construct(FeatureFlagsRepository $feature_flags_repository) {
    $this->feature_flags_repository = $feature_flags_repository;
  }

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
    $features = $this->feature_flags_repository->findAll();
    $featuresMap = [];
    foreach ($features as $feature) {
      $featuresMap[$feature->getName()] = (bool)$feature->getValue();
    }
    return $featuresMap;
  }
}
