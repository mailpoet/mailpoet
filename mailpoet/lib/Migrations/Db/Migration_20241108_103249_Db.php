<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Automation\Engine\Utils\Json;
use MailPoet\Migrator\DbMigration;

/**
 * Fixes WooCommerce Subscriptions status filter in automations (e.g. trigger filters or if/else conditions)
 * - Changes `field_type` from `enum_array` to `enum`
 * - Changes condition from `matches-*` to `is-*`
 * - Strips `wc-` prefix from values
 */
class Migration_20241108_103249_Db extends DbMigration {
  public function run(): void {
    global $wpdb;
    $automationVersionsTable = esc_sql($wpdb->prefix . 'mailpoet_automation_versions');

    $wooSubscriptionAutomations = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT `id`, `steps` FROM %i WHERE `steps` LIKE %s OR `steps` LIKE %s",
        $automationVersionsTable,
        '%woocommerce-subscriptions:subscription:status%',
        '%woocommerce-subscriptions:subscription:billing-period%'
      ),
      ARRAY_A
    );

    foreach ($wooSubscriptionAutomations as $automation) {
      // Parse JSON encoded automation steps
      try {
        $jsonData = Json::decode($automation['steps']);
      } catch (\Exception $e) {
        continue;
      }

      // Iterate over each automation step
      foreach ($jsonData as $key => &$item) {
        if (!isset($item['filters']['groups']) || !is_array($item['filters']['groups'])) {
          continue;
        }

        // Iterate over each step filter group
        // This can be e.g. a trigger filter, or if/else condition
        foreach ($item['filters']['groups'] as &$group) {
          if (!isset($group['filters']) || !is_array($group['filters'])) {
            continue;
          }
          foreach ($group['filters'] as &$filter_item) {
            $this->fixWooSubscriptionFilters($filter_item);
          }
        }
      }
      $updatedJson = json_encode($jsonData);
      $wpdb->update($automationVersionsTable, ['steps' => $updatedJson], ['id' => $automation['id']]);
    }
  }

  private function fixWooSubscriptionFilters(&$filter_item): void {
    // Only fix WooCommerce Subscriptions status and billing period filters
    if (!isset($filter_item['field_key'])) {
      return;
    }
    $isStatusField = $filter_item['field_key'] === 'woocommerce-subscriptions:subscription:status';
    $isBillingPeriodField = $filter_item['field_key'] === 'woocommerce-subscriptions:subscription:billing-period';
    if (!$isStatusField && !$isBillingPeriodField) {
      return;
    }

    // Fix field_type from enum_array to enum
    if (isset($filter_item['field_type']) && $filter_item['field_type'] === 'enum_array') {
      $filter_item['field_type'] = 'enum';
    }

    // Fix condition
    if (isset($filter_item['condition'])) {
      $conditionMap = [
        'matches-any-of' => 'is-any-of',
        'matches-all-of' => 'is-any-of',
        'matches-none-of' => 'is-none-of',
      ];
      $filter_item['condition'] = $conditionMap[$filter_item['condition']] ?? $filter_item['condition'];
    }

    // Strip "wc-" prefix from values but only in status field
    if (!$isStatusField) {
      return;
    }
    if (isset($filter_item['args']['value']) && is_array($filter_item['args']['value'])) {
      $filter_item['args']['value'] = array_map(function($value) {
        return preg_replace('/^wc-/', '', $value);
      }, $filter_item['args']['value']);
    }
  }
}
