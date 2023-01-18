<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

class Tracker {
  /**
   * @param array $data
   * @return array
   */
  public function addTrackingData($data): array {
    if (!is_array($data)) {
      return $data;
    }
    $data['extensions']['mailpoet']['campaign_revenues'] = [];
    return $data;
  }
}
