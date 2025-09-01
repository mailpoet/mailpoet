<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\WooCommerceBookings;

use MailPoet\WP\Functions;

class Helper {

  private Functions $wp;

  public function __construct(
    Functions $wp
  ) {
    $this->wp = $wp;
  }

  public function isWooCommerceBookingsActive(): bool {
    return $this->wp->isPluginActive('woocommerce-bookings/woocommerce-bookings.php');
  }
}
