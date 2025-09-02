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

  public function getBookingStatuses(): array {
    if (!function_exists('get_wc_booking_statuses')) {
      return [];
    }

    return get_wc_booking_statuses('fully_booked', true);
  }

  /**
   * @param int $id
   * @return false|\WC_Booking
   */
  public function getBooking(int $id) {
    if (!function_exists('get_wc_booking')) {
      return false;
    }

    return get_wc_booking($id);
  }
}
