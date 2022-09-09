<?php

/*
Plugin Name: MailPoet Woo COT Helper
Description: Adds functionality for testing WooCommerce COT feature
Author: MailPoet
Version: 1.0
*/

use \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use \Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;

/**
 * Enable WooCommerce COT tables
 * @see https://github.com/Automattic/woocommerce/wiki/COT-Upgrade-Recipe-Book#easy-way
 */

function mailpoet_enable_cot(): void {
  if (!function_exists('wc_get_container')) {
    return;
  }
  /** @var CustomOrdersTableController $orderController */
  $orderController = wc_get_container()->get(CustomOrdersTableController::class);
  if ($orderController instanceof CustomOrdersTableController) {
    $orderController->show_feature();
  }
}
add_action( 'init', 'mailpoet_enable_cot', 99 );


/**
 * Add wp create_cot WP CLI command for creating Custom Order Tables from command line
 */
function mailpoet_create_cot() {
  if (!function_exists('wc_get_container')) {
    WP_CLI::error('Can‘t create COT. WooCommerce is not active!');
  }
  try {
    /** @var DataSynchronizer $dataSynchronizer */
    $dataSynchronizer = wc_get_container()->get(DataSynchronizer::class);
  } catch (\Exception $e) {
    WP_CLI::error('DataSynchronizer for COT not found. Does installed WooCommerce version support COT? ' . $e->getMessage());
    return;
  }
  $dataSynchronizer->create_database_tables();
  WP_CLI::success('Database tables for COT feature created.');
}

if (class_exists(WP_CLI::class)) {
  WP_CLI::add_command('create_cot', 'mailpoet_create_cot');
}
