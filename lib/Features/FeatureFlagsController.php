<?php

namespace MailPoet\Features;

use MailPoet\Models\FeatureFlag;
use function MailPoet\Util\array_column;

class FeatureFlagsController {
  function set($name, $value) {
    if (!isset(FeaturesController::$defaults[$name])) {
      throw new \RuntimeException("Feature '$name' does not exist'");
    }

    $result = FeatureFlag::createOrUpdate([
      'name' => $name,
      'value' => $value ,
    ]);

    if ($result->getErrors()) {
      throw new \RuntimeException("Error when saving feature '$name''");
    }
  }

  function getAll() {
    $flags = FeatureFlag::findArray();
    $flagsMap = array_combine(array_column($flags, 'name'), $flags);

    $output = [];
    foreach (FeaturesController::$defaults as $name => $default) {
      $output[] = [
        'name' => $name,
        'value' => isset($flagsMap[$name]) ? (bool)$flagsMap[$name]['value'] : $default,
        'default' => $default,
      ];
    }
    return $output;
  }
}
