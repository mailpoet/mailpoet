<?php

namespace MailPoet\Features;

class FeaturesController {

  // Define features below in the following form:
  //   const FEATURE_NAME_OF_FEATURE = 'name-of-feature';

  // Define feature defaults in the array below in the following form:
  //   self::FEATURE_NAME_OF_FEATURE => true,
  public static $defaults = [
  ];

  /** @return bool */
  function isSupported($feature) {
    if (!array_key_exists($feature, self::$defaults)) {
      throw new \RuntimeException("Unknown feature '$feature'");
    }
    return self::$defaults[$feature];
  }
}
