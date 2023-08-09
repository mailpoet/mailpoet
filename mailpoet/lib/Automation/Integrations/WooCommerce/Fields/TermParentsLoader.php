<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use MailPoet\Automation\Engine\WordPress;

class TermParentsLoader {
  /** @var WordPress */
  private $wordPress;

  public function __construct(
    WordPress $wordPress
  ) {
    $this->wordPress = $wordPress;
  }

  /**
   * @param int[] $termIds
   * @return int[]
   */
  public function getParentIds(array $termIds): array {
    $idsPlaceholder = implode(',', array_fill(0, count($termIds), '%s'));

    $wpdb = $this->wordPress->getWpdb();
    $statement = (string)$wpdb->prepare("
      SELECT DISTINCT tt.parent
      FROM {$wpdb->term_taxonomy} AS tt
      WHERE tt.parent != 0
      AND tt.term_id IN ($idsPlaceholder)
    ", $termIds);

    $parentIds = array_map('intval', $wpdb->get_col($statement));
    if (count($parentIds) === 0) {
      return [];
    }
    return array_values(
      array_unique(
        array_merge($parentIds, $this->getParentIds($parentIds))
      )
    );
  }
}
