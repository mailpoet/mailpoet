<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Models\FeatureFlag as FeatureFlagModel;

class Features {

  function withFeatureEnabled($name) {
    FeatureFlagModel::createOrUpdate([
      'name' => $name,
      'value' => true,
    ]);
    return $this;
  }

  function withFeatureDisabled($name) {
    FeatureFlagModel::createOrUpdate([
      'name' => $name,
      'value' => false,
    ]);
    return $this;
  }
}
