<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

class TermParentsLoader {
  /**
   * @param int[] $termIds
   * @return int[]
   */
  public function getParentIds(array $termIds): array {
    global $wpdb;
    if (count($termIds) === 0) {
      return [];
    }

    $result = $wpdb->get_col(
      $wpdb->prepare(
        "
          SELECT DISTINCT tt.parent
          FROM {$wpdb->term_taxonomy} AS tt
          WHERE tt.parent != 0
          AND tt.term_id IN (" . implode(',', array_fill(0, count($termIds), '%s')) . ")
        ",
        $termIds
      )
    );
    $parentIds = array_map('intval', $result);
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
